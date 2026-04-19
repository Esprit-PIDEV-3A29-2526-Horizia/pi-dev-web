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

        if (strtoupper((string) $reservation->getStatut()) !== 'CONFIRMEE') {
            $this->addFlash('error', 'Le paiement est disponible uniquement pour les réservations confirmées.');

            return $this->redirectToRoute('app_front_reservation_detail', [
                'id' => $reservation->getId(),
            ]);
        }

        $paymentStatus = method_exists($reservation, 'getPaymentStatus')
            ? strtoupper((string) $reservation->getPaymentStatus())
            : 'NON_PAYEE';

        if ($paymentStatus === 'PAYEE') {
            $this->addFlash('success', 'Cette réservation est déjà payée.');

            return $this->redirectToRoute('app_front_reservation_detail', [
                'id' => $reservation->getId(),
            ]);
        }

        if (method_exists($reservation, 'getPrixTotal') && $reservation->getPrixTotal() !== null) {
            $prixTotal = (float) $reservation->getPrixTotal();
        } else {
            $prixTotal = (float) $reservation->getVoyage()->getPrix() * (int) $reservation->getNbrPersonnes();
        }

        if ($prixTotal <= 0) {
            $this->addFlash('error', 'Montant de paiement invalide.');

            return $this->redirectToRoute('app_front_reservation_detail', [
                'id' => $reservation->getId(),
            ]);
        }

        $successUrl = $request->getSchemeAndHttpHost() . '/payment/success/' . $reservation->getId();
        $cancelUrl = $request->getSchemeAndHttpHost() . '/payment/cancel/' . $reservation->getId();

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

        if (method_exists($reservation, 'setPaymentStatus')) {
            $reservation->setPaymentStatus('PAYEE');
            $entityManager->flush();
        }

        $this->addFlash('success', 'Le paiement a été effectué avec succès.');

        return $this->render('front/reservation/reservation_detail.html.twig', [
            'reservation' => $reservation,
            'currency' => 'TND',
        ]);
    }

    #[Route('/payment/cancel/{id}', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(int $id, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $this->addFlash('error', 'Le paiement a été annulé.');

        return $this->render('front/reservation/reservation_detail.html.twig', [
            'reservation' => $reservation,
            'currency' => 'TND',
        ]);
    }
}