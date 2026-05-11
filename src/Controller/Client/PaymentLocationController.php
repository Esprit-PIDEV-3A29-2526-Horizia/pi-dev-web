<?php

namespace App\Controller\Client;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/paiement')]
#[IsGranted('ROLE_USER')]
class PaymentLocationController extends AbstractController
{
    public function __construct(
        private string $stripeSecretKey,
        private string $stripePublicKey,
    ) {}

    // ══════════════════════════════════════════
    // PAGE CHOIX DU PAIEMENT (Total ou Avance)
    // ══════════════════════════════════════════
    #[Route('/choisir/{id}', name: 'client_paiement_choisir', methods: ['GET'])]
    public function choisir(int $id, LocationRepository $locationRepo): Response
    {
        $location = $locationRepo->find($id);

        if (!$location) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($location->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Si déjà payé, rediriger vers confirmation
        if ($location->getStatut() === 'payée') {
            return $this->redirectToRoute('client_confirmation', ['id' => $id]);
        }

        return $this->render('client/paiement_choisir.html.twig', [
            'location'          => $location,
            'stripe_public_key' => $this->stripePublicKey,
            'user'              => $user,
        ]);
    }

    // ══════════════════════════════════════════
    // CRÉER SESSION STRIPE
    // ══════════════════════════════════════════
    #[Route('/stripe/{id}/{type}', name: 'client_paiement_stripe', methods: ['GET'])]
    public function stripe(
        int $id,
        string $type, // 'total' ou 'avance'
        LocationRepository $locationRepo
    ): Response {
        $location = $locationRepo->find($id);

        if (!$location) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($location->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        Stripe::setApiKey($this->stripeSecretKey);

        // Déterminer le montant à payer
        if ($type === 'avance') {
            $montant     = (float) $location->getAvance();
            $description = 'Avance (30%) - Location #' . $location->getIdLocation();
            $label       = 'Avance de 30%';
        } else {
            $montant     = (float) $location->getMontantTotal();
            $description = 'Paiement total - Location #' . $location->getIdLocation();
            $label       = 'Montant total';
        }

        // Stripe travaille en centimes
        $montantCentimes = (int) round($montant * 100);

        $vehicule = $location->getVehicule();
        $nomVehicule = $vehicule
            ? ($vehicule->getModele()?->getNomModele() ?? 'Véhicule') . ' - ' . ($vehicule->getImmatriculation() ?? '')
            : 'Location de véhicule';

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $montantCentimes,
                    'product_data' => [
                        'name'        => $nomVehicule,
                        'description' => $description,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode'          => 'payment',
            'success_url'   => $this->generateUrl(
                'client_paiement_success',
                ['id' => $id, 'type' => $type],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cancel_url'    => $this->generateUrl(
                'client_paiement_choisir',
                ['id' => $id],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'customer_email' => $user->getEmail(),
            'metadata'       => [
                'location_id' => (string) $id,
                'type'        => (string) $type,
                'user_id'     => (string) $user->getId(),
            ],
        ]);

        return $this->redirect($session->url, 303);
    }

    // ══════════════════════════════════════════
    // SUCCESS — après paiement Stripe
    // ══════════════════════════════════════════
    #[Route('/success/{id}/{type}', name: 'client_paiement_success', methods: ['GET'])]
    public function success(
        int $id,
        string $type,
        LocationRepository $locationRepo,
        EntityManagerInterface $em
    ): Response {
        $location = $locationRepo->find($id);

        if (!$location) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

       if ($type === 'total') {
    $location->setStatut('en_cours');
    $this->addFlash('success', '🎉 Paiement total effectué ! Votre réservation est entièrement soldée.');
} else {
    $location->setStatut('réservée');
    $this->addFlash('success', '✅ Avance payée avec succès ! Le reste sera réglé à la prise du véhicule.');
}

        $em->flush();

        return $this->redirectToRoute('client_confirmation', ['id' => $id]);
    }

    // ══════════════════════════════════════════
    // CANCEL — paiement annulé
    // ══════════════════════════════════════════
    #[Route('/cancel/{id}', name: 'client_paiement_cancel', methods: ['GET'])]
    public function cancel(int $id): Response
    {
        $this->addFlash('warning', 'Paiement annulé. Vous pouvez réessayer à tout moment.');
        return $this->redirectToRoute('client_paiement_choisir', ['id' => $id]);
    }
}