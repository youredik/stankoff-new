<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

class ThumbnailService
{
    public function generateImageThumbnail(string $imagePath, string $thumbnailPath, int $maxWidth = 200, int $maxHeight = 200): void
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is not loaded');
        }

        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new \RuntimeException('Invalid image file');
        }

        $mimeType = $imageInfo['mime'];
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // Create image resource based on type
        $sourceImage = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($imagePath),
            'image/png' => imagecreatefrompng($imagePath),
            'image/gif' => imagecreatefromgif($imagePath),
            'image/webp' => imagecreatefromwebp($imagePath),
            default => throw new \RuntimeException('Unsupported image type: ' . $mimeType),
        };

        if (!$sourceImage) {
            throw new \RuntimeException('Failed to create image resource');
        }

        // Calculate thumbnail dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        if (!$thumbnail) {
            throw new \RuntimeException('Failed to create thumbnail');
        }

        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }

        // Resize
        if (!imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
            throw new \RuntimeException('Failed to resize image');
        }

        // Save thumbnail
        $result = match ($mimeType) {
            'image/jpeg' => imagejpeg($thumbnail, $thumbnailPath, 85),
            'image/png' => imagepng($thumbnail, $thumbnailPath),
            'image/gif' => imagegif($thumbnail, $thumbnailPath),
            'image/webp' => imagewebp($thumbnail, $thumbnailPath),
            default => false,
        };

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);

        if (!$result) {
            throw new \RuntimeException('Failed to save thumbnail');
        }
    }

    public function generateVideoThumbnail(string $videoPath, string $thumbnailPath, int $timeOffset = 1): void
    {
        // Use ffmpeg to extract a frame
        $command = sprintf(
            'ffmpeg -i %s -ss %d -vframes 1 -q:v 2 -f image2 %s 2>&1',
            escapeshellarg($videoPath),
            $timeOffset,
            escapeshellarg($thumbnailPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to generate video thumbnail: ' . implode("\n", $output));
        }

        // Now resize the extracted frame if it's too large
        if (file_exists($thumbnailPath)) {
            $this->generateImageThumbnail($thumbnailPath, $thumbnailPath, 200, 200);
        }
    }
}
