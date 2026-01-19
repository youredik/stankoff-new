<?php

declare(strict_types=1);

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class YandexObjectStorageService
{
    private S3Client $s3Client;
    private string $bucketName;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->bucketName = $parameterBag->get('yandex_object_storage.bucket_name');

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'ru-central1',
            'endpoint' => 'https://storage.yandexcloud.net',
            'credentials' => [
                'key' => $parameterBag->get('yandex_object_storage.access_key'),
                'secret' => $parameterBag->get('yandex_object_storage.secret_key'),
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    public function uploadFile(string $key, string $filePath, string $contentType = 'application/octet-stream'): void
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'SourceFile' => $filePath,
            'ContentType' => $contentType,
            'ACL' => 'private',
        ]);
    }

    public function uploadContent(string $key, string $content, string $contentType = 'application/octet-stream'): void
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'Body' => $content,
            'ContentType' => $contentType,
            'ACL' => 'private',
        ]);
    }

    public function downloadFile(string $key): string
    {
        $result = $this->s3Client->getObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
        ]);

        return (string) $result['Body'];
    }

    public function getPresignedUrl(string $key, int $expires = 3600): string
    {
        $cmd = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucketName,
            'Key' => $key,
        ]);

        $request = $this->s3Client->createPresignedRequest($cmd, "+{$expires} seconds");

        return (string) $request->getUri();
    }

    public function deleteFile(string $key): void
    {
        $this->s3Client->deleteObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
        ]);
    }

    public function fileExists(string $key): bool
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
