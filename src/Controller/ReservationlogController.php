<?php

namespace App\Controller;

use App\Entity\Logement;
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
use App\Service\EmailService;

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

        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une réservation.');
            return $this->redirectToRoute('app_login');
        }

        $dateArrivee = \DateTime::createFromFormat('Y-m-d', (string) $request->request->get('date_arrivee'));
        $dateDepart  = \DateTime::createFromFormat('Y-m-d', (string) $request->request->get('date_depart'));
        $modalite    = (string) $request->request->get('modalite');

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

        // At this point $dateArrivee and $dateDepart are guaranteed non-false
        /** @var \DateTime $dateArrivee */
        /** @var \DateTime $dateDepart */
        $nuits   = $dateArrivee->diff($dateDepart)->days;
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
            return $this->redirectToRoute('app_front_reservationlog_index');
        }

        $reservation->setStatus('en_attente');
        $em->persist($reservation);
        $em->flush();

        $successUrl = $this->generateUrl('app_front_reservation_success', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->generateUrl('app_front_reservation_cancel', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);

        $stripeUrl = $stripeService->createCheckoutSession(
            (string) $logement->getNom(),
            (int) round($montant * 100),
            $successUrl,
            $cancelUrl
        );

        return $this->redirect($stripeUrl);
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
        return $this->redirectToRoute('app_front_reservationlog_index');
    }

    #[Route('/reservation/cancel/{id}', name: 'app_front_reservation_cancel')]
    public function paymentCancel(int $id, EntityManagerInterface $em, EmailService $emailService): Response
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if ($reservation && $reservation->getStatus() === 'en_attente') {
            $reservation->setStatus('annulée');
            $em->flush();
            $this->addFlash('error', 'Paiement annulé. Réservation annulée.');
        }
        if ($reservation !== null) {
            $userObj = $reservation->getUser();
            if ($userObj !== null) {
                $emailService->sendCancellationEmail(
                    (string) $userObj->getEmail(),
                    $reservation,
                    'Paiement annulé par l\'utilisateur'
                );
            }
        }
        return $this->redirectToRoute('app_front_reservationlog_index');
    }

    #[Route('/reservation/create-ajax', name: 'app_front_reservation_create_ajax', methods: ['POST'])]
    public function createReservationAjax(
        Request $request,
        EntityManagerInterface $em,
        LogementRepository $logementRepository,
        EmailService $emailService,
        StripeService $stripeService
    ): JsonResponse {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['success' => false, 'message' => 'Vous devez être connecté pour effectuer une réservation.'], 401);
            }

            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['success' => false, 'message' => 'JSON invalide.'], 400);
            }

            $logementId          = $data['logement_id'] ?? null;
            $dateArriveeStr      = $data['date_arrivee'] ?? null;
            $dateDepartStr       = $data['date_depart'] ?? null;
            $modalite            = $data['modalite'] ?? null;
            $paiementImmediat    = $data['paiement_immediat'] ?? false;
            $adultes             = (int) ($data['adultes'] ?? 1);
            $enfants             = (int) ($data['enfants'] ?? 0);
            $nombreChambres      = (int) ($data['nombre_chambres'] ?? 1);
            $modeReservation     = $data['mode_reservation'] ?? null;
            $repartitionChambres = $data['repartition_chambres'] ?? null;

            if (!$logementId || !$dateArriveeStr || !$dateDepartStr || !$modalite) {
                return $this->json(['success' => false, 'message' => 'Données manquantes.'], 400);
            }
            if ($adultes < 1) {
                return $this->json(['success' => false, 'message' => 'Au moins 1 adulte est requis.'], 400);
            }

            $validModes = ['logement_petit_dejeuner', 'demi_pension', 'all_inclusive', 'all_inclusive_soft(sans_alcool)'];
            if ($modeReservation !== null && !in_array($modeReservation, $validModes)) {
                return $this->json(['success' => false, 'message' => 'Veuillez choisir une formule de pension valide.'], 400);
            }

            $logement = $logementRepository->find($logementId);
            if (!$logement || !$logement->isDisponibilite()) {
                return $this->json(['success' => false, 'message' => 'Logement non disponible.'], 400);
            }

            $typeLogement = strtolower((string) $logement->getType());
            $isHotel = ($typeLogement === 'hôtel' || $typeLogement === 'hotel');
            if (!$isHotel) {
                $nombreChambres = 1;
            }

            $dateArrivee = \DateTime::createFromFormat('Y-m-d', (string) $dateArriveeStr);
            $dateDepart  = \DateTime::createFromFormat('Y-m-d', (string) $dateDepartStr);
            $today = new \DateTime();
            $today->setTime(0, 0, 0);

            if (!$dateArrivee || !$dateDepart || $dateArrivee < $today || $dateDepart <= $dateArrivee) {
                return $this->json(['success' => false, 'message' => 'Dates invalides.'], 400);
            }

            if (!$this->isCapacityAvailable($logement, $dateArrivee, $dateDepart, $adultes, $enfants, $em)) {
                return $this->json(['success' => false, 'message' => '❌ Désolé, le logement a atteint sa capacité maximale sur cette période. Veuillez choisir d\'autres dates.'], 400);
            }

            $nuits                      = $dateArrivee->diff($dateDepart)->days;
            $nombrePersonnes            = $adultes + $enfants;
            $prixBaseParNuitParPersonne = $logement->getTarifNuit();
            $coefficient                = $this->getPensionCoefficient((string) $modeReservation);
            $montant                    = $nuits * $nombrePersonnes * $prixBaseParNuitParPersonne * $coefficient;

            $stripeUrl = null;
            if ($modalite === 'Sur place') {
                $status  = 'en_attente';
                $message = 'Réservation enregistrée avec succès (paiement sur place).';
            } else {
                if ($paiementImmediat === true) {
                    $status  = 'confirmée';
                    $message = 'Réservation confirmée ! Redirection vers le paiement...';
                } else {
                    $status  = 'en_attente';
                    $message = 'Réservation en attente. Vous pouvez la finaliser dans les 24h.';
                }
            }

            $reservation = new Reservationlog();
            $reservation->setLogement($logement);
            $reservation->setUser($user);
            $reservation->setDateDebut($dateArrivee);
            $reservation->setDateFin($dateDepart);
            $reservation->setMontant($montant);
            $reservation->setModalites((string) $modalite);
            $reservation->setStatus($status);
            $reservation->setAdultes($adultes);
            $reservation->setEnfants($enfants);
            $reservation->setNombreChambres($nombreChambres);
            $reservation->setModeReservation((string) $modeReservation);
            $reservation->setCreatedAt(new \DateTime());
            $reservation->setRepartitionChambres((string) ($repartitionChambres ?? ''));

            $em->persist($reservation);
            $em->flush();

            if ($modalite === 'En ligne' && $paiementImmediat === true) {
                $successUrl = $this->generateUrl('app_front_reservation_success', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);
                $cancelUrl  = $this->generateUrl('app_front_reservation_cancel', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);
                $stripeUrl  = $stripeService->createCheckoutSession(
                    (string) $logement->getNom(),
                    (int) round($montant * 100),
                    $successUrl,
                    $cancelUrl
                );
            }

            $emailService->sendReservationEmail((string) $user->getEmail(), $reservation, $message);

            return $this->json([
                'success'        => true,
                'message'        => $message,
                'status'         => $status,
                'reservation_id' => $reservation->getIdreslog(),
                'stripe_url'     => $stripeUrl,
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur interne : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/reservation/pay/{id}', name: 'app_front_reservation_pay', methods: ['GET'])]
    public function payReservation(int $id, EntityManagerInterface $em, StripeService $stripeService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer un paiement.');
            return $this->redirectToRoute('app_login');
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getUser() === null || $reservation->getUser()->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }
        if ($reservation->getStatus() !== 'en_attente' || $reservation->getModalites() !== 'En ligne') {
            $this->addFlash('error', 'Cette réservation ne peut pas être payée.');
            return $this->redirectToRoute('app_front_reservationlog_index');
        }

        // Correction PHPStan : $logement récupéré depuis la réservation (était undefined)
        $logement = $reservation->getLogement();
        if ($logement === null) {
            throw $this->createNotFoundException('Logement introuvable pour cette réservation.');
        }

        $successUrl = $this->generateUrl('app_front_reservation_success', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->generateUrl('app_front_reservation_cancel', ['id' => $reservation->getIdreslog()], UrlGeneratorInterface::ABSOLUTE_URL);
        $stripeUrl  = $stripeService->createCheckoutSession(
            (string) $logement->getNom(),
            (int) round(($reservation->getMontant() ?? 0) * 100),
            $successUrl,
            $cancelUrl
        );

        return $this->redirect($stripeUrl);
    }

    private function isCapacityAvailable(Logement $logement, \DateTime $dateArrivee, \DateTime $dateDepart, int $adultes, int $enfants, EntityManagerInterface $em): bool
    {
        $qb = $em->createQueryBuilder();
        $qb->select('SUM(r.adultes + r.enfants) as total_personnes')
           ->from(Reservationlog::class, 'r')
           ->where('r.logement = :logement')
           ->andWhere('r.date_debut < :depart AND r.date_fin > :arrivee')
           ->setParameter('logement', $logement)
           ->setParameter('arrivee', $dateArrivee)
           ->setParameter('depart', $dateDepart);

        $result              = $qb->getQuery()->getSingleScalarResult();
        $personnesExistantes = $result ? (int) $result : 0;
        $nouvellesPersonnes  = $adultes + $enfants;
        return ($personnesExistantes + $nouvellesPersonnes) <= $logement->getCapacite();
    }

    #[Route('/test-email', name: 'test_email')]
    public function testEmail(EmailService $emailService, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('Aucun utilisateur connecté', 400);
        }
        $reservation = $em->getRepository(Reservationlog::class)->findOneBy(['user' => $user]);
        if ($reservation) {
            $emailService->sendReservationEmail((string) $user->getEmail(), $reservation, 'Test message');
            return new Response('Email envoyé (vérifiez les logs)');
        }
        return new Response('Aucune réservation trouvée pour cet utilisateur');
    }

    private function getPensionCoefficient(?string $modeReservation): float
    {
        return match ($modeReservation) {
            'demi_pension'                    => 1.20,
            'all_inclusive'                   => 1.45,
            'all_inclusive_soft(sans_alcool)' => 1.37,
            'logement_petit_dejeuner'         => 1.00,
            default                           => 1.00,
        };
    }
}