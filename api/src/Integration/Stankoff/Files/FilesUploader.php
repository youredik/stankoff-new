<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Files;

use App\Entity\SupportTicketMedia;
use App\Integration\Stankoff\Client\StankoffPermanentException;
use App\Integration\Stankoff\Client\StankoffTransientException;
use App\Service\YandexObjectStorageService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Three-phase file upload for the Stankoff Files API:
 *   1. POST /files/request-upload  -> { file.id, uploadUrl, uploadFields }
 *   2. multipart POST uploadUrl    -> 204 (Yandex Object Storage)
 *   3. POST /files/{fileId}/confirm -> { file.status: ready }
 *
 * Idempotency: each successful confirm yields a fileId; the caller persists
 * those into outbox.uploaded_file_ids and passes them on retry so we skip
 * already-uploaded media (Stankoff Files API itself is NOT idempotent).
 */
final class FilesUploader
{
    public function __construct(
        #[Autowire(env: 'STANKOFF_BASE_URL')] private readonly string $baseUrl,
        #[Autowire(env: 'STANKOFF_API_KEY')] private readonly string $apiKey,
        private readonly HttpClientInterface $httpClient,
        private readonly YandexObjectStorageService $sourceStorage,
    ) {
    }

    /**
     * Uploads any media that has not been uploaded yet (per per-media tracking).
     * Returns the ordered list of fileIds matching the current media collection.
     *
     * Skip strategy is keyed by mediaId, not by index — so if media is added or
     * removed between retries, we still upload exactly what's needed and avoid
     * uploading the same media twice (Stankoff Files API has no idempotency key).
     *
     * @param iterable<SupportTicketMedia> $media
     * @param array<int,string> $alreadyUploaded map of mediaId => fileId
     * @param callable(int,string): void $onUploaded notified per (mediaId, fileId) for outbox persistence
     * @return list<string>
     */
    public function uploadAll(
        iterable $media,
        array $alreadyUploaded,
        callable $onUploaded,
    ): array {
        $result = [];
        foreach ($media as $m) {
            $mid = $m->getId();
            if ($mid === null) {
                throw new StankoffTransientException('Media without id encountered, cannot track upload state');
            }

            if (isset($alreadyUploaded[$mid])) {
                $result[] = $alreadyUploaded[$mid];
                continue;
            }

            $fileId = $this->uploadOne($m);
            $result[] = $fileId;
            $onUploaded($mid, $fileId);
        }
        return $result;
    }

    private function uploadOne(SupportTicketMedia $media): string
    {
        $tmpPath = $this->downloadToTmp($media);
        try {
            $upload = $this->requestUpload($media);
            $fileId = (string)($upload['data']['file']['id'] ?? '');
            if ($fileId === '') {
                throw new StankoffTransientException(
                    'Stankoff request-upload returned no file id: ' . json_encode($upload, JSON_UNESCAPED_UNICODE),
                );
            }

            $uploadUrl = (string)($upload['data']['uploadUrl'] ?? '');
            $uploadFields = (array)($upload['data']['uploadFields'] ?? []);

            $this->multipartUpload($uploadUrl, $uploadFields, $tmpPath, $media->mimeType, $media->originalName);
            $this->confirm($fileId);

            return $fileId;
        } finally {
            @unlink($tmpPath);
        }
    }

    private function downloadToTmp(SupportTicketMedia $media): string
    {
        $tmpPath = sys_get_temp_dir() . '/stankoff_upload_' . uniqid('', true);
        try {
            $bytes = $this->sourceStorage->downloadFile($media->path);
            file_put_contents($tmpPath, $bytes);
            return $tmpPath;
        } catch (\Throwable $e) {
            @unlink($tmpPath);
            throw new StankoffTransientException(
                "Failed to download {$media->path} from source storage: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /** @return array<string,mixed> */
    private function requestUpload(SupportTicketMedia $media): array
    {
        $url = rtrim($this->baseUrl, '/') . '/api/v1/integrations/files/request-upload';
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'filename' => $media->originalName,
                    'contentType' => $media->mimeType,
                    'sizeBytes' => $media->size,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'timeout' => 10,
                'max_duration' => 15,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getContent(throw: false);

            if ($status >= 500) {
                throw new StankoffTransientException("Files API request-upload {$status}", httpStatus: $status, responseBody: $body);
            }
            if ($status >= 400) {
                throw new StankoffPermanentException(
                    "Files API request-upload rejected {$status}: {$body}",
                    httpStatus: $status,
                    responseBody: $body,
                );
            }

            return json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        } catch (TransportExceptionInterface | HttpClientException $e) {
            throw new StankoffTransientException("Files API request-upload network error: {$e->getMessage()}", previous: $e);
        } catch (\JsonException $e) {
            throw new StankoffTransientException("Files API request-upload bad JSON response: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * @param array<string,string> $fields fields from uploadFields
     */
    private function multipartUpload(string $url, array $fields, string $localPath, string $contentType, string $filename): void
    {
        // Yandex Object Storage signs the policy with the declared Content-Type;
        // sending anything else fails with SignatureDoesNotMatch / 403.
        $parts = [];
        foreach ($fields as $k => $v) {
            $parts[$k] = (string)$v;
        }
        $parts['file'] = DataPart::fromPath($localPath, $filename, $contentType);

        $form = new FormDataPart($parts);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $form->getPreparedHeaders()->toArray(),
                'body' => $form->bodyToIterable(),
                'timeout' => 30,
                'max_duration' => 300, // up to 5 min for big files
            ]);

            $status = $response->getStatusCode();
            if ($status !== 204 && $status !== 200) {
                $body = $response->getContent(throw: false);
                if ($status >= 500) {
                    throw new StankoffTransientException("S3 upload {$status}", httpStatus: $status, responseBody: $body);
                }
                throw new StankoffPermanentException(
                    "S3 upload rejected {$status}: " . substr($body, 0, 1000),
                    httpStatus: $status,
                    responseBody: $body,
                );
            }
        } catch (TransportExceptionInterface | HttpClientException $e) {
            throw new StankoffTransientException("S3 upload network error: {$e->getMessage()}", previous: $e);
        }
    }

    private function confirm(string $fileId): void
    {
        $url = rtrim($this->baseUrl, '/') . '/api/v1/integrations/files/' . rawurlencode($fileId) . '/confirm';
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => 10,
                'max_duration' => 15,
            ]);

            $status = $response->getStatusCode();
            if ($status >= 500) {
                throw new StankoffTransientException("Files API confirm {$status}", httpStatus: $status);
            }
            if ($status >= 400) {
                $body = $response->getContent(throw: false);
                throw new StankoffPermanentException(
                    "Files API confirm rejected {$status}: {$body}",
                    httpStatus: $status,
                    responseBody: $body,
                );
            }
        } catch (TransportExceptionInterface | HttpClientException $e) {
            throw new StankoffTransientException("Files API confirm network error: {$e->getMessage()}", previous: $e);
        }
    }
}
