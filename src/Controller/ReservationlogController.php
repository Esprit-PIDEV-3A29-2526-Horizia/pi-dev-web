<?php

namespace App\Controller;

use App\Entity\Reservationlog;
use App\Entity\User;
use App\Repository\LogementRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReservationlogController extends AbstractController
{
    #[Route('/logement/{id}/reserver-modal', name: 'app_front_reservation_modal', methods: ['GET'])]
    public function reservationModal(int $id, LogementRepository $logementRepository): Response
    {
        $logement = $logementRepository->find($id);
        if (!$logement) throw $this->createNotFoundException();
        return $this->render('front/reservationlog/_form_modal.html.twig', ['logement' => $logement]);
    }

    #[Route('/logement/{id}/reserver', name: 'app_front_reservation_new')]
    public function new(Request $request, int $id, LogementRepository $logementRepository, EntityManagerInterface $em, StripeService $stripeService): Response
    {
        $logement = $logementRepository->find($id);
        if (!$logement) throw $this->createNotFoundException();

        $user = $em->getRepository(User::class)->find(1);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur de test non trouvé.');
            return $this->redirectToRoute('app_front_logement_index');
        }

        $dateArrivee = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_arrivee'));
        $dateDepart  = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_depart'));
        $modalite    = $request->request->get('modalite');

        $errors = [];
        if (!$dateArrivee || !$dateDepart) {
            $errors[] = 'Dates invalides.';
        } elseif ($dateArrivee < new \DateTime() || $dateDepart <= $dateArrivee) {
            $errors[] = 'Les dates doivent être valides (départ après arrivée, et non passées).';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) $this->addFlash('error', $error);
            return $this->redirectToRoute('app_front_logement_index');
        }

        $nuits = $dateArrivee->diff($dateDepart)->days;
        $montant = $nuits * $logement->getTarifNuit();

        $reservation = new Reservationlog();
        $reservation->setLogement($logement);
        $reservation->setUser($user);
        $reservation->setDateDebut($dateArrivee);
        $reservation->setDateFin($dateDepart);
        $reservation->setMontant($montant);
        $reservation->setModalites($modalite);

        if ($modalite === 'Sur place') {
            $reservation->setStatus('en_attente');
            $em->persist($reservation);
            $em->flush();
            $this->addFlash('success', 'Réservation enregistrée avec succès (paiement sur place).');
            return $this->redirectToRoute('app_front_reservation_index');
        }

        $reservation->setStatus('en_attente');
        $em->persist($reservation);
        $em->flush();

        $successUrl = $this->generateUrl('app_front_reservation_success', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->generateUrl('app_front_reservation_cancel', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = $stripeService->createCheckoutSession($montant, 'eur', $successUrl, $cancelUrl);

        return $this->redirect($session->url);
    }

    #[Route('/reservation/success/{id}', name: 'app_front_reservation_success')]
    public function paymentSuccess(int $id, EntityManagerInterface $em): Response
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if ($reservation && $reservation->getStatus() === 'en_attente') {
            $reservation->setStatus('confirmée');
            $em->flush();
            $this->addFlash('success', 'Paiement accepté. Réservation confirmée !');
        }
        return $this->redirectToRoute('app_front_reservation_index');
    }

    #[Route('/reservation/cancel/{id}', name: 'app_front_reservation_cancel')]
    public function paymentCancel(int $id, EntityManagerInterface $em): Response
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if ($reservation && $reservation->getStatus() === 'en_attente') {
            $reservation->setStatus('annulée');
            $em->flush();
            $this->addFlash('error', 'Paiement annulé. Réservation annulée.');
        }
        return $this->redirectToRoute('app_front_reservation_index');
    }
#[Route('/reservation/create-ajax', name: 'app_front_reservation_create_ajax', methods: ['POST'])]
public function createReservationAjax(Request $request, EntityManagerInterface $em, LogementRepository $logementRepository): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    $logementId = $data['logement_id'] ?? null;
    $dateArriveeStr = $data['date_arrivee'] ?? null;
    $dateDepartStr = $data['date_depart'] ?? null;
    $modalite = $data['modalite'] ?? null;
    $paiementImmediat = $data['paiement_immediat'] ?? false;

    if (!$logementId || !$dateArriveeStr || !$dateDepartStr || !$modalite) {
        return $this->json(['success' => false, 'message' => 'Données manquantes.'], 400);
    }

    $logement = $logementRepository->find($logementId);
    if (!$logement || !$logement->isDisponibilite()) {
        return $this->json(['success' => false, 'message' => 'Logement non disponible.'], 400);
    }

    $user = $this->getUser();
    if (!$user) {
        $user = $em->getRepository(User::class)->find(1);
        if (!$user) $user = $em->getRepository(User::class)->findOneBy([]);
    }
    if (!$user) {
        return $this->json(['success' => false, 'message' => 'Aucun utilisateur trouvé.'], 400);
    }

    $dateArrivee = \DateTime::createFromFormat('Y-m-d', $dateArriveeStr);
    $dateDepart = \DateTime::createFromFormat('Y-m-d', $dateDepartStr);
    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    if (!$dateArrivee || !$dateDepart || $dateArrivee < $today || $dateDepart <= $dateArrivee) {
        return $this->json(['success' => false, 'message' => 'Dates invalides.'], 400);
    }

    $nuits = $dateArrivee->diff($dateDepart)->days;
    $montant = $nuits * $logement->getTarifNuit();

    // Messages et statut selon le cas
    if ($modalite === 'Sur place') {
        $status = 'en_attente';
        $message = 'Réservation enregistrée avec succès (paiement sur place).';
    } else { // En ligne
        if ($paiementImmediat === true) {
            $status = 'confirmée';
            $message = 'Réservation confirmée ! Redirection vers le paiement...';
        } else {
            $status = 'en_attente';
            $message = 'Réservation en attente. Vous pouvez la finaliser dans les 24h.';
        }
    }

    $reservation = new Reservationlog();
    $reservation->setLogement($logement);
    $reservation->setUser($user);
    $reservation->setDateDebut($dateArrivee);
    $reservation->setDateFin($dateDepart);
    $reservation->setMontant($montant);
    $reservation->setModalites($modalite);
    $reservation->setStatus($status);
    // $reservation->setDateReservation(new \DateTime()); // si le champ existe

    $em->persist($reservation);
    $em->flush();

    return $this->json([
        'success' => true,
        'message' => $message,
        'status' => $status,
        'reservation_id' => $reservation->getIdreslog()
    ]);
}
}