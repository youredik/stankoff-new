<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SupportTicketMediaRepository;
use App\Service\YandexObjectStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/support_tickets/{supportTicket}/media/{media}/download', name: 'support_ticket_media_download')]
#[IsGranted('OIDC_SUPPORT_EMPLOYEE')]
#[IsGranted('OIDC_SUPPORT_MANAGER')]
class SupportTicketMediaController extends AbstractController
{
    public function __construct(
        private readonly SupportTicketMediaRepository $mediaRepository,
        private readonly YandexObjectStorageService $storageService,
    ) {
    }

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
}
