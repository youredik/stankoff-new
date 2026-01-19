<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SupportTicketMediaRepository;
use App\Service\YandexObjectStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

#[IsGranted(new Expression('is_granted("OIDC_SUPPORT_EMPLOYEE") or is_granted("OIDC_SUPPORT_MANAGER") or is_granted("OIDC_ADMIN")'))]
class SupportTicketMediaController extends AbstractController
{
    public function __construct(
        private readonly SupportTicketMediaRepository $mediaRepository,
        private readonly YandexObjectStorageService $storageService,
    ) {
    }

    #[Route('/api/support_tickets/{supportTicket}/media/{media}/download', name: 'support_ticket_media_download')]
    public function __invoke(int $supportTicket, int $media): Response
    {
        $mediaEntity = $this->mediaRepository->find($media);
        if (!$mediaEntity || $mediaEntity->supportTicket->getId() !== $supportTicket) {
            throw $this->createNotFoundException('Media not found');
        }

        $content = $this->storageService->downloadFile($mediaEntity->path);

        return new Response($content, 200, [
            'Content-Type' => $mediaEntity->mimeType,
            'Content-Disposition' => 'attachment; filename="' . $mediaEntity->originalName . '"',
        ]);
    }

    #[Route('/api/support_tickets/{supportTicket}/media/{media}/thumbnail', name: 'support_ticket_media_thumbnail')]
    public function thumbnail(int $supportTicket, int $media): Response
    {
        $mediaEntity = $this->mediaRepository->find($media);
        if (!$mediaEntity || $mediaEntity->supportTicket->getId() !== $supportTicket || !$mediaEntity->thumbnailPath) {
            throw $this->createNotFoundException('Thumbnail not found');
        }

        $content = $this->storageService->downloadFile($mediaEntity->thumbnailPath);

        return new Response($content, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="thumbnail_' . $mediaEntity->originalName . '"',
        ]);
    }
}
