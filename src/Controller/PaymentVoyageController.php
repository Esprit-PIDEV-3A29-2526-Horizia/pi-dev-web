<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentVoyageController extends AbstractController
{
    #[Route('/payment/checkout/{id}', name: 'app_payment_checkout', methods: ['GET'])]
    public function checkout(
        int $id,
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
        Request $request
    ): RedirectResponse {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        if (!$reservation->getVoyage()) {
            throw $this->createNotFoundException('Aucun voyage lié à cette réservation.');
        }

        $successUrl = $request->getSchemeAndHttpHost() . '/payment/success/' . $reservation->getId();
        $cancelUrl = $request->getSchemeAndHttpHost() . '/payment/cancel/' . $reservation->getId();

        $prixTotal = $reservation->getVoyage()->getPrix() * $reservation->getNbrPersonnes();
        $amountInMinorUnit = (int) round($prixTotal * 100);

        $checkoutUrl = $stripeService->createCheckoutSession(
            'Paiement réservation #' . $reservation->getId(),
            $amountInMinorUnit,
            $successUrl,
            $cancelUrl,
            [
                'reservation_id' => (string) $reservation->getId(),
            ]
        );

        return $this->redirect($checkoutUrl);
    }

    #[Route('/payment/success/{id}', name: 'app_payment_success', methods: ['GET'])]
    public function success(int $id, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('front/success.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/payment/cancel/{id}', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(int $id, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('front/cancel.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}