<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupportTicketMedia;
use App\Repository\SupportTicketRepository;
use App\Service\ThumbnailService;
use App\Service\YandexObjectStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class SupportTicketMediaCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SupportTicketRepository $supportTicketRepository,
        private YandexObjectStorageService $storageService,
        private ThumbnailService $thumbnailService,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): SupportTicketMedia {
        $request = $context['request'] ?? null;
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Invalid request');
        }

        $supportTicketId = $uriVariables['supportTicket'] ?? null;
        if (!$supportTicketId) {
            throw new BadRequestHttpException('Support ticket ID is required');
        }

        $supportTicket = $this->supportTicketRepository->find($supportTicketId);
        if (!$supportTicket) {
            throw new NotFoundHttpException('Support ticket not found');
        }

        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile instanceof UploadedFile) {
            throw new BadRequestHttpException('No file uploaded');
        }

        if (!$uploadedFile->isValid()) {
            throw new BadRequestHttpException('File upload failed: ' . $uploadedFile->getError());
        }

        // Validate file type
        $allowedMimeTypes = [
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            // Videos
            'video/mp4',
            'video/avi',
            'video/mov',
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            // Archives
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            // Text files
            'text/plain',
            'text/csv',
        ];
        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            throw new BadRequestHttpException('Invalid file type. Allowed types: images, videos, PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV, ZIP, RAR, 7Z.');
        }

        // Validate file size (max 100MB)
        if ($uploadedFile->getSize() > 100 * 1024 * 1024) {
            throw new BadRequestHttpException('File size too large. Maximum size is 100MB.');
        }

        // Generate unique filename
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = uniqid('media_', true) . '.' . $extension;
        $path = "support-ticket/media/{$supportTicketId}/{$filename}";

        // Upload to storage
        $this->storageService->uploadFile($path, $uploadedFile->getPathname(), $uploadedFile->getMimeType());

        // Generate thumbnail if it's an image or video
        $thumbnailPath = null;
        if (str_starts_with($uploadedFile->getMimeType(), 'image/')) {
            try {
                $thumbnailFilename = 'thumb_' . $filename;
                $thumbnailLocalPath = sys_get_temp_dir() . '/' . $thumbnailFilename;
                $this->thumbnailService->generateImageThumbnail($uploadedFile->getPathname(), $thumbnailLocalPath);
                $thumbnailStoragePath = "support-ticket/media/{$supportTicketId}/{$thumbnailFilename}";
                $this->storageService->uploadFile($thumbnailStoragePath, $thumbnailLocalPath, 'image/jpeg');
                $thumbnailPath = $thumbnailStoragePath;
                unlink($thumbnailLocalPath); // Clean up temp file
            } catch (\Exception $e) {
                // Log error but don't fail the upload
                error_log('Failed to generate image thumbnail: ' . $e->getMessage());
            }
        } elseif (str_starts_with($uploadedFile->getMimeType(), 'video/')) {
            try {
                $thumbnailFilename = 'thumb_' . $filename . '.jpg';
                $thumbnailLocalPath = sys_get_temp_dir() . '/' . $thumbnailFilename;
                $this->thumbnailService->generateVideoThumbnail($uploadedFile->getPathname(), $thumbnailLocalPath);
                $thumbnailStoragePath = "media/{$supportTicketId}/{$thumbnailFilename}";
                $this->storageService->uploadFile($thumbnailStoragePath, $thumbnailLocalPath, 'image/jpeg');
                $thumbnailPath = $thumbnailStoragePath;
                unlink($thumbnailLocalPath);
            } catch (\Exception $e) {
                error_log('Failed to generate video thumbnail: ' . $e->getMessage());
            }
        } elseif ($uploadedFile->getMimeType() === 'application/pdf') {
            try {
                $thumbnailFilename = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
                $thumbnailLocalPath = sys_get_temp_dir() . '/' . $thumbnailFilename;
                $this->thumbnailService->generatePdfThumbnail($uploadedFile->getPathname(), $thumbnailLocalPath);
                $thumbnailStoragePath = "support-ticket/media/{$supportTicketId}/{$thumbnailFilename}";
                $this->storageService->uploadFile($thumbnailStoragePath, $thumbnailLocalPath, 'image/jpeg');
                $thumbnailPath = $thumbnailStoragePath;
                unlink($thumbnailLocalPath);
            } catch (\Exception $e) {
                error_log('Failed to generate PDF thumbnail: ' . $e->getMessage());
            }
        } elseif (in_array($uploadedFile->getMimeType(), [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], true)) {
            try {
                $thumbnailFilename = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
                $thumbnailLocalPath = sys_get_temp_dir() . '/' . $thumbnailFilename;
                $this->thumbnailService->generateOfficeThumbnail($uploadedFile->getPathname(), $thumbnailLocalPath);
                $thumbnailStoragePath = "support-ticket/media/{$supportTicketId}/{$thumbnailFilename}";
                $this->storageService->uploadFile($thumbnailStoragePath, $thumbnailLocalPath, 'image/jpeg');
                $thumbnailPath = $thumbnailStoragePath;
                unlink($thumbnailLocalPath);
            } catch (\Exception $e) {
                error_log('Failed to generate office document thumbnail: ' . $e->getMessage());
            }
        }

        // Create media entity
        $media = new SupportTicketMedia();
        $media->filename = $filename;
        $media->originalName = $uploadedFile->getClientOriginalName();
        $media->mimeType = $uploadedFile->getMimeType();
        $media->size = $uploadedFile->getSize();
        $media->path = $path;
        $media->thumbnailPath = $thumbnailPath;
        $media->supportTicket = $supportTicket;

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }
}
