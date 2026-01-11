<?php

namespace App\Controller;

use App\Repository\OldSiteApiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    public function __construct(private readonly OldSiteApiRepository $oldSiteApi)
    {
    }

    #[Route('/api/orders/{id}', methods: ['GET'])]
    public function getOrder(int $id): JsonResponse
    {
        $orderData = $this->oldSiteApi->getOrder($id);
        $order = [
            'id' => $orderData['id'],
            'number' => $orderData['number'],
            'counterparty_name' => $orderData['counterparty_name'],
            'counterparty_inn' => $orderData['counterparty_inn'],
            'counterparty_kpp' => $orderData['counterparty_kpp'],
            'manager' => $orderData['manager'],
            'items' => $orderData['items'] ?? [],
        ];
        return new JsonResponse([
            'order' => $order,
        ]);
    }
}
