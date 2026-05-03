<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Reservation;
use App\Entity\Reservationlog;
use App\Entity\User;
use App\Entity\Voyage;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $session = $requestStack->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
    }

    private function checkAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user instanceof User || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $voyageRepository = $entityManager->getRepository(Voyage::class);
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        $userRepository = $entityManager->getRepository(User::class);
        $categorieRepository = $entityManager->getRepository(Categorie::class);

        $nbVoyages = $voyageRepository->count([]);
        $nbReservations = $reservationRepository->count([]);
        $nbUsers = $userRepository->count([]);
        $nbCategories = $categorieRepository->count([]);

        $nbReservationsConfirmees = $reservationRepository->count(['statut' => 'CONFIRMEE']);
        $nbReservationsEnAttente = $reservationRepository->count(['statut' => 'EN_ATTENTE']);
        $nbReservationsAnnulees = $reservationRepository->count(['statut' => 'ANNULEE']);

        $recentReservations = $entityManager->createQueryBuilder()
            ->select('r', 'v', 'u')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('r.user', 'u')
            ->orderBy('r.dateReservation', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $recentVoyages = $entityManager->createQueryBuilder()
            ->select('v')
            ->from(Voyage::class, 'v')
            ->orderBy('v.dateDepart', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $reservationsForChart = $entityManager->createQueryBuilder()
            ->select('r.dateReservation')
            ->from(Reservation::class, 'r')
            ->getQuery()
            ->getResult();

        $monthlyReservations = array_fill(1, 12, 0);

        foreach ($reservationsForChart as $row) {
            if (!empty($row['dateReservation']) && $row['dateReservation'] instanceof \DateTimeInterface) {
                $monthNumber = (int) $row['dateReservation']->format('n');
                $monthlyReservations[$monthNumber]++;
            }
        }

        $confirmedReservationsForChart = $entityManager->createQueryBuilder()
            ->select('r.dateReservation')
            ->from(Reservation::class, 'r')
            ->where('r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->getQuery()
            ->getResult();

        $monthlyConfirmedReservations = array_fill(1, 12, 0);

        foreach ($confirmedReservationsForChart as $row) {
            if (!empty($row['dateReservation']) && $row['dateReservation'] instanceof \DateTimeInterface) {
                $monthNumber = (int) $row['dateReservation']->format('n');
                $monthlyConfirmedReservations[$monthNumber]++;
            }
        }

        $topVoyagesRaw = $entityManager->createQueryBuilder()
            ->select('v.titre AS titre', 'COUNT(r.id) AS nbReservations')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->groupBy('v.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $topVoyagesLabels = [];
        $topVoyagesData = [];

        foreach ($topVoyagesRaw as $row) {
            $topVoyagesLabels[] = $row['titre'] ?? 'Voyage';
            $topVoyagesData[] = (int) $row['nbReservations'];
        }

        $topClientsRaw = $entityManager->createQueryBuilder()
            ->select(
                "CONCAT(COALESCE(u.nom, ''), ' ', COALESCE(u.prenom, '')) AS clientNom",
                'COUNT(r.id) AS nbReservations'
            )
            ->from(Reservation::class, 'r')
            ->leftJoin('r.user', 'u')
            ->where('u.id IS NOT NULL')
            ->groupBy('u.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $topClientsLabels = [];
        $topClientsData = [];

        foreach ($topClientsRaw as $row) {
            $nom = trim((string) ($row['clientNom'] ?? ''));
            $topClientsLabels[] = $nom !== '' ? $nom : 'Utilisateur';
            $topClientsData[] = (int) $row['nbReservations'];
        }

        $categoriesStatsRaw = $entityManager->createQueryBuilder()
            ->select('c.nom AS categorieNom', 'COUNT(r.id) AS nbReservations')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('v.categorie', 'c')
            ->groupBy('c.id')
            ->orderBy('nbReservations', 'DESC')
            ->getQuery()
            ->getResult();

        $categoriesLabels = [];
        $categoriesData = [];

        foreach ($categoriesStatsRaw as $row) {
            $categoriesLabels[] = $row['categorieNom'] ?? 'Sans catégorie';
            $categoriesData[] = (int) $row['nbReservations'];
        }

        $confirmedRevenueRows = $entityManager->createQueryBuilder()
            ->select('r', 'v')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->where('r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->getQuery()
            ->getResult();

        $revenuTotalEstime = 0.0;

        foreach ($confirmedRevenueRows as $reservation) {
            $voyage = $reservation->getVoyage();

            if ($voyage) {
                $prixAdulte = (float) $voyage->getPrix();
                $nbAdultes = (int) ($reservation->getNbAdultes() ?? 0);
                $nbEnfants = (int) ($reservation->getNbEnfants() ?? 0);

                $revenuTotalEstime += ($prixAdulte * $nbAdultes) + (($prixAdulte * 0.5) * $nbEnfants);
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
            'nbReservationsConfirmees' => $nbReservationsConfirmees,
            'nbReservationsEnAttente' => $nbReservationsEnAttente,
            'nbReservationsAnnulees' => $nbReservationsAnnulees,
            'recentReservations' => $recentReservations,
            'recentVoyages' => $recentVoyages,
            'revenuTotalEstime' => $revenuTotalEstime,

            'chartLabels' => ['Voyages', 'Réservations', 'Utilisateurs', 'Catégories'],
            'chartData' => [$nbVoyages, $nbReservations, $nbUsers, $nbCategories],

            'lineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'lineChartData' => array_values($monthlyReservations),

            'confirmedLineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'confirmedLineChartData' => array_values($monthlyConfirmedReservations),

            'topVoyagesLabels' => $topVoyagesLabels,
            'topVoyagesData' => $topVoyagesData,

            'topClientsLabels' => $topClientsLabels,
            'topClientsData' => $topClientsData,

            'categoriesLabels' => $categoriesLabels,
            'categoriesData' => $categoriesData,
        ]);
    }

    #[Route('/admin/reservations/cancel-requests', name: 'admin_cancel_requests')]
    public function cancelRequests(EntityManagerInterface $em): Response
    {
        $this->checkAdminAccess();

        $requests = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $pendingCancelRequests = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.idreslog)')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/cancel_requests.html.twig', [
            'requests' => $requests,
            'pendingCancelRequests' => $pendingCancelRequests,
        ]);
    }

    #[Route('/admin/reservation/approve-cancel/{id}', name: 'admin_approve_cancel', methods: ['POST'])]
    public function approveCancel(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $this->checkAdminAccess();

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getStatus() !== 'demande_annulation') {
            return $this->json(['error' => 'Réservation non trouvée ou pas en demande d\'annulation'], 404);
        }

        $reservation->setStatus('annulée');
        $em->flush();

        $user = $reservation->getUser();
        $email = $user?->getEmail() ?? '';
        if (!empty($email)) {
$emailService->sendCancellationEmail($email, $reservation, 'Votre demande d\'annulation a été approuvée.');        }

        $this->addFlash('success', '✅ Annulation approuvée pour la réservation #' . $reservation->getIdreslog());
        $this->requestStack->getSession()->save();

        $userSession = $this->requestStack->getSession();
        $userId = $user?->getId() ?? 0;
        $notifications = $userSession->get('user_notifications_' . $userId, []);
        $notifications[] = [
            'message' => '✅ Votre demande d\'annulation pour la réservation #' . $reservation->getIdreslog() . ' a été acceptée.',
            'type' => 'success'
        ];
        $userSession->set('user_notifications_' . $userId, $notifications);
        $userSession->save();

        return $this->json(['success' => true, 'message' => 'Annulation confirmée.']);
    }

    #[Route('/admin/reservation/reject-cancel/{id}', name: 'admin_reject_cancel', methods: ['POST'])]
    public function rejectCancel(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $this->checkAdminAccess();

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getStatus() !== 'demande_annulation') {
            return $this->json(['error' => 'Réservation non trouvée ou pas en demande d\'annulation'], 404);
        }

        $reservation->setStatus('confirmée');
        $em->flush();

        $user = $reservation->getUser();
        $email = $user?->getEmail() ?? '';
$emailService->sendReservationEmail($email, $reservation, 'Votre demande d\'annulation a été rejetée. Votre réservation reste confirmée.');
        $this->addFlash('warning', '⚠️ Demande d\'annulation rejetée pour la réservation #' . $reservation->getIdreslog());
        $this->requestStack->getSession()->save();

        $userSession = $this->requestStack->getSession();
        $userId = $user?->getId() ?? 0;
        $notifications = $userSession->get('user_notifications_' . $userId, []);
        $notifications[] = [
            'message' => '❌ Votre demande d\'annulation pour la réservation #' . $reservation->getIdreslog() . ' a été rejetée.',
            'type' => 'error'
        ];
        $userSession->set('user_notifications_' . $userId, $notifications);
        $userSession->save();

        return $this->json(['success' => true, 'message' => 'Demande rejetée.']);
    }
       
    
    #[Route('/admin/location-dashboard', name: 'admin_location_dashboard')]
    public function locationDashboard(
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkAdminAccess();
        
        // Récupérer les repositories
        $vehiculeRepository = $entityManager->getRepository(\App\Entity\Vehicule::class);
        $locationRepository = $entityManager->getRepository(\App\Entity\Location::class);
        
        // Statistiques
        $vehiculesDisponibles = $vehiculeRepository->count(['etat' => 'disponible']);
        $vehiculesLoues = $vehiculeRepository->count(['etat' => 'louee']);
        $locationsActives = $locationRepository->count(['statut' => 'en_cours']);
        
        // CA du mois
        $mois = (int) date('m');
        $annee = (int) date('Y');
        
        $premierJour = new \DateTime("{$annee}-{$mois}-01");
        $dernierJour = new \DateTime("{$annee}-{$mois}-" . $premierJour->format('t'));
        
        $locationsMois = $entityManager->createQueryBuilder()
            ->select('l')
            ->from(\App\Entity\Location::class, 'l')
            ->where('l.dateDebut BETWEEN :debut AND :fin')
            ->setParameter('debut', $premierJour)
            ->setParameter('fin', $dernierJour)
            ->getQuery()
            ->getResult();
        
        $caMois = 0;
        foreach ($locationsMois as $loc) {
            $caMois += (float) $loc->getMontantTotal();
        }
        
        // Répartition des statuts
        $statuts = [];
        $allLocations = $locationRepository->findAll();
        foreach ($allLocations as $loc) {
            $statut = $loc->getStatut() ?? 'Non défini';
            if (!isset($statuts[$statut])) {
                $statuts[$statut] = 0;
            }
            $statuts[$statut]++;
        }
        
        // Taux d'occupation
        $totalVehicules = $vehiculeRepository->count([]);
        $tauxOccupation = $totalVehicules > 0 ? round(($vehiculesLoues / $totalVehicules) * 100, 1) : 0;
        
        // Top modèles loués
        $topModeles = $entityManager->createQueryBuilder()
            ->select('m.nomModele', 'mar.nomMarque', 'COUNT(l.idLocation) as total')
            ->from(\App\Entity\Location::class, 'l')
            ->leftJoin('l.vehicule', 'v')
            ->leftJoin('v.modele', 'm')
            ->leftJoin('m.marque', 'mar')
            ->groupBy('m.idModele')
            ->orderBy('total', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        $top5Modeles = [];
        $topModele = ['total' => 0, 'nomMarque' => '', 'nomModele' => ''];
        
        if (!empty($topModeles)) {
            $topModele = [
                'total' => $topModeles[0]['total'],
                'nomMarque' => $topModeles[0]['nomMarque'],
                'nomModele' => $topModeles[0]['nomModele']
            ];
            foreach ($topModeles as $tm) {
                $key = $tm['nomMarque'] . ' ' . $tm['nomModele'];
                $top5Modeles[$key] = $tm['total'];
            }
        }
        
        // Alertes retour (locations qui se terminent dans les 3 jours)
        $aujourdhui = new \DateTime();
        $dateLimite = (clone $aujourdhui)->modify('+3 days');
        
        $alertesRetour = $entityManager->createQueryBuilder()
            ->select('l')
            ->from(\App\Entity\Location::class, 'l')
            ->where('l.dateFinPrevue BETWEEN :aujourdhui AND :limite')
            ->andWhere('l.statut NOT IN (:statuts)')
            ->setParameter('aujourdhui', $aujourdhui)
            ->setParameter('limite', $dateLimite)
            ->setParameter('statuts', ['terminée', 'annulée'])
            ->getQuery()
            ->getResult();
        
        // Locations pour la carte (avec coordonnées)
        $locationsCartographie = $entityManager->createQueryBuilder()
            ->select('l', 'v')
            ->from(\App\Entity\Location::class, 'l')
            ->leftJoin('l.vehicule', 'v')
            ->where('l.statut IN (:statuts)')
            ->setParameter('statuts', ['en_cours', 'réservée'])
            ->getQuery()
            ->getResult();
        
        // Noms des mois en français
        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        $moisNom = $moisNoms[$mois] . ' ' . $annee;
        
        return $this->render('admin/dashboard/index.html.twig', [
            'moisNom' => $moisNom,
            'vehiculesDisponibles' => $vehiculesDisponibles,
            'vehiculesLoues' => $vehiculesLoues,
            'locationsActives' => $locationsActives,
            'caMois' => $caMois,
            'tauxOccupation' => $tauxOccupation,
            'statuts' => $statuts,
            'alertesRetour' => $alertesRetour,
            'locationsCartographie' => $locationsCartographie,
            'top5Modeles' => $top5Modeles,
            'topModele' => $topModele,
        ]);
    }
}