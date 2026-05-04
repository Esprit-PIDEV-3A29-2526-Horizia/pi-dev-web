<?php

namespace App\Controller\Front;

use App\Entity\Reservation;
use App\Entity\Events;
use App\Entity\Voyage;
use App\Entity\Logement;
use App\Entity\Reservationlog;
use App\Entity\User;
use App\Service\GeminiService;
use App\Service\LogementSearchService;
use App\Service\OpenWeatherService;
use App\Service\CurrencyService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Doctrine\ORM\Tools\Pagination\Paginator;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_front_home')]
    public function index(
        ManagerRegistry $doctrine,
        OpenWeatherService $openWeatherService,
        CurrencyService $currencyService,
        Request $request
    ): Response {
        $entityManager = $doctrine->getManager();

        $currency = $currencyService->normalizeCurrency($request->query->get('currency', 'TND'));

        $query = $doctrine->getRepository(Voyage::class)->createQueryBuilder('v')
            ->select('v', 'COUNT(r.id) AS HIDDEN nbReservations')
            ->innerJoin(Reservation::class, 'r', 'WITH', 'r.voyage = v AND r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->groupBy('v.id')
            ->orderBy('nbReservations', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setMaxResults(3)
            ->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $voyages = iterator_to_array($paginator);

        $weatherData = [];
        $convertedPrices = [];

        foreach ($voyages as $voyage) {
            $destination = trim((string) $voyage->getDestination());

            if ($destination !== '') {
                $weatherData[$voyage->getId()] = $openWeatherService->getWeatherByCity($destination);
            } else {
                $weatherData[$voyage->getId()] = null;
            }

            $convertedPrices[$voyage->getId()] = $currencyService->convert(
                (float) $voyage->getPrix(),
                $currency
            );
        }

        return $this->render('front/index.html.twig', [
            'voyages' => $voyages,
            'weatherData' => $weatherData,
            'currency' => $currency,
            'currencySymbol' => $currencyService->getSymbol($currency),
            'convertedPrices' => $convertedPrices,
            'allowedCurrencies' => $currencyService->getAllowedCurrencies(),
        ]);
    }

    #[Route('/home', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_front_home');
    }

    #[Route('/voyage/{id}', name: 'app_front_voyage_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(
        int $id,
        ManagerRegistry $doctrine,
        OpenWeatherService $openWeatherService,
        CurrencyService $currencyService,
        Request $request
    ): Response {
        $voyage = $doctrine->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $weather = null;
        $destination = trim((string) $voyage->getDestination());

        if ($destination !== '') {
            $weather = $openWeatherService->getWeatherByCity($destination);
        }

        $currency = $currencyService->normalizeCurrency($request->query->get('currency', 'TND'));

        $convertedPrice = $currencyService->convert(
            (float) $voyage->getPrix(),
            $currency
        );

        return $this->render('front/voyages/detail.html.twig', [
            'voyage' => $voyage,
            'weather' => $weather,
            'currency' => $currency,
            'currencySymbol' => $currencyService->getSymbol($currency),
            'convertedPrice' => $convertedPrice,
            'allowedCurrencies' => $currencyService->getAllowedCurrencies(),
        ]);
    }

    #[Route('/voyage/{id}/reserver', name: 'app_front_reserver_voyage', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function reserver(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        EntityManagerInterface $entityManager,
        CurrencyService $currencyService,
        OpenWeatherService $openWeatherService
    ): Response {
        $voyage = $doctrine->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        if ($voyage->getPlacesRestantes() <= 0) {
            $this->addFlash('error', 'Désolé, ce voyage est complet.');

            return $this->redirectToRoute('app_front_voyage_detail', [
                'id' => $voyage->getId(),
                'currency' => $request->query->get('currency', 'TND'),
            ]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $prixEnfantRatio = 0.5;
        $currency = $currencyService->normalizeCurrency($request->query->get('currency', 'TND'));

        $prixAdulteConverti = $currencyService->convert((float) $voyage->getPrix(), $currency);
        $prixEnfantConverti = $currencyService->convert((float) ($voyage->getPrix() * $prixEnfantRatio), $currency);

        $weather = null;
        $destination = trim((string) $voyage->getDestination());

        if ($destination !== '') {
            $weather = $openWeatherService->getWeatherByCity($destination);
        }

        if ($request->isMethod('POST')) {
            $nbAdultes = max(0, (int) $request->request->get('nbAdultes', 0));
            $nbEnfants = max(0, (int) $request->request->get('nbEnfants', 0));
            $nbPersonnes = $nbAdultes + $nbEnfants;

            if ($nbPersonnes <= 0) {
                $this->addFlash('error', 'Veuillez sélectionner au moins 1 personne.');

                return $this->redirectToRoute('app_front_reserver_voyage', [
                    'id' => $voyage->getId(),
                    'currency' => $currency,
                ]);
            }

            if ($nbPersonnes > $voyage->getPlacesRestantes()) {
                $this->addFlash('error', 'Le nombre de places demandées dépasse les places restantes.');

                return $this->redirectToRoute('app_front_reserver_voyage', [
                    'id' => $voyage->getId(),
                    'currency' => $currency,
                ]);
            }

            $reservation = new Reservation();
            $reservation->setVoyage($voyage);
            $reservation->setUser($user);
            $reservation->setDateReservation(new \DateTime());
            $reservation->setStatut('EN_ATTENTE');

            if (method_exists($reservation, 'setPaymentStatus')) {
                $reservation->setPaymentStatus('NON_PAYEE');
            }

            if (method_exists($reservation, 'setNbAdultes')) {
                $reservation->setNbAdultes($nbAdultes);
            }

            if (method_exists($reservation, 'setNbEnfants')) {
                $reservation->setNbEnfants($nbEnfants);
            }

            if (method_exists($reservation, 'recalculerNbrPersonnes')) {
                $reservation->recalculerNbrPersonnes();
            } else {
                $reservation->setNbrPersonnes($nbPersonnes);
            }

            $prixTotalDt = ($voyage->getPrix() * $nbAdultes) + (($voyage->getPrix() * $prixEnfantRatio) * $nbEnfants);

            if (method_exists($reservation, 'setPrixTotal')) {
                $reservation->setPrixTotal(new \Money\Money((int)($prixTotalDt * 100), new \Money\Currency('TND')));
            }

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de réservation a bien été enregistrée.');

            return $this->redirectToRoute('app_front_mes_reservations', [
                'currency' => $currency,
            ]);
        }

        return $this->render('front/reservation/reservation.html.twig', [
            'voyage' => $voyage,
            'prixEnfantRatio' => $prixEnfantRatio,
            'currency' => $currency,
            'currencySymbol' => $currencyService->getSymbol($currency),
            'allowedCurrencies' => $currencyService->getAllowedCurrencies(),
            'prixAdulteConverti' => $prixAdulteConverti,
            'prixEnfantConverti' => $prixEnfantConverti,
            'weather' => $weather,
        ]);
    }

    #[Route('/mes-reservations', name: 'app_front_mes_reservations', methods: ['GET'])]
    public function mesReservations(
        Request $request,
        ManagerRegistry $doctrine,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $currency = $request->query->get('currency', 'TND');
        $selectedStatut = trim((string) $request->query->get('statut', ''));
        $page = $request->query->getInt('page', 1);

        $qb = $doctrine->getManager()->getRepository(Reservation::class)->createQueryBuilder('r')            
            ->innerJoin('r.voyage', 'v')
            ->addSelect('v')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.dateReservation', 'DESC');

        if ($selectedStatut !== '') {
            if (strtoupper($selectedStatut) === 'PAYEE' && $this->reservationHasField($doctrine, 'paymentStatus')) {
                $qb->andWhere('UPPER(r.paymentStatus) = :paymentStatus')
                    ->setParameter('paymentStatus', 'PAYEE');
            } else {
                $qb->andWhere('UPPER(r.statut) = :statut')
                    ->setParameter('statut', strtoupper($selectedStatut));
            }
        }

        $reservations = $paginator->paginate($qb, $page, 6);

        return $this->render('front/reservation/mes_reservations.html.twig', [
            'reservations' => $reservations,
            'selectedStatut' => $selectedStatut,
            'currency' => $currency,
        ]);
    }

    #[Route('/mes-reservations/{id}', name: 'app_front_reservation_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detailReservation(int $id, ManagerRegistry $doctrine): Response
    {
        $reservation = $doctrine->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (
            method_exists($reservation, 'getUser') &&
            $reservation->getUser() &&
            $reservation->getUser()->getId() !== $user->getId() &&
            !in_array('ROLE_ADMIN', $user->getRoles())
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('front/reservation/reservation_detail.html.twig', [
            'reservation' => $reservation,
            'currency' => 'TND',
        ]);
    }

    #[Route('/reservation/{id}/annuler', name: 'app_front_annuler_reservation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annulerReservation(
        int $id,
        ManagerRegistry $doctrine,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $reservation = $doctrine->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (
            method_exists($reservation, 'getUser') &&
            $reservation->getUser() &&
            $reservation->getUser()->getId() !== $user->getId() &&
            !in_array('ROLE_ADMIN', $user->getRoles())
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (
            !$this->isCsrfTokenValid(
                'annuler_reservation_' . $reservation->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $paymentStatus = method_exists($reservation, 'getPaymentStatus')
            ? strtoupper((string) $reservation->getPaymentStatus())
            : 'NON_PAYEE';

        if (strtoupper((string) $reservation->getStatut()) === 'ANNULEE') {
            $this->addFlash('error', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        if (strtoupper((string) $reservation->getStatut()) === 'CONFIRMEE') {
            $this->addFlash('error', 'Une réservation confirmée ne peut pas être annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        if ($paymentStatus === 'PAYEE') {
            $this->addFlash('error', 'Une réservation payée ne peut pas être annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        $reservation->setStatut('ANNULEE');
        $entityManager->flush();

        $this->addFlash('success', 'La réservation a bien été annulée.');

        return $this->redirectToRoute('app_front_mes_reservations');
    }

    #[Route('/voyages', name: 'app_front_voyages', methods: ['GET'])]
    public function voyages(ManagerRegistry $doctrine, Request $request, OpenWeatherService $openWeatherService, CurrencyService $currencyService, PaginatorInterface $paginator ): Response {
        $qb = $doctrine->getRepository(Voyage::class)->createQueryBuilder('v')
            ->orderBy('v.id', 'DESC');

        $filters = [
            'budget_max' => trim((string) $request->query->get('budget_max', '')),
            'destination' => trim((string) $request->query->get('destination', '')),
            'search' => trim((string) $request->query->get('search', '')),
            'currency' => $currencyService->normalizeCurrency($request->query->get('currency', 'TND')),
        ];

        if ($filters['budget_max'] !== '') {
            $qb->andWhere('v.prix <= :budgetMax')
                ->setParameter('budgetMax', (float) $filters['budget_max']);
        }

        if ($filters['destination'] !== '') {
            $qb->andWhere('LOWER(v.destination) LIKE :destination')
                ->setParameter('destination', '%' . mb_strtolower($filters['destination']) . '%');
        }

        if ($filters['search'] !== '') {
            $qb->andWhere('LOWER(v.titre) LIKE :search OR LOWER(v.destination) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($filters['search']) . '%');
        }

        $currency = $filters['currency'];

        $voyages = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            6
        );

        $weatherData = [];
        $convertedPrices = [];

        foreach ($voyages as $voyage) {
            $destination = trim((string) $voyage->getDestination());

            if ($destination !== '') {
                $weatherData[$voyage->getId()] = $openWeatherService->getWeatherByCity($destination);
            } else {
                $weatherData[$voyage->getId()] = null;
            }

            $convertedPrices[$voyage->getId()] = $currencyService->convert(
                (float) $voyage->getPrix(),
                $currency
            );
        }

        return $this->render('front/voyages/voyages.html.twig', [
            'voyages' => $voyages,
            'weatherData' => $weatherData,
            'convertedPrices' => $convertedPrices,
            'currency' => $currency,
            'currencySymbol' => $currencyService->getSymbol($currency),
            'allowedCurrencies' => $currencyService->getAllowedCurrencies(),
            'filters' => $filters,
        ]);
    }

    #[Route('/reservation/{id}/qrcode', name: 'app_reservation_qrcode', methods: ['GET'])]
    public function reservationQrCode(int $id, ManagerRegistry $doctrine): Response
    {
        $reservation = $doctrine->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $result = $this->buildReservationQrCode($reservation);

        return new Response(
            $result->getString(),
            200,
            [
                'Content-Type' => $result->getMimeType(),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    #[Route('/reservation/{id}/qrcode/download', name: 'app_reservation_qrcode_download', methods: ['GET'])]
    public function downloadReservationQrCode(int $id, ManagerRegistry $doctrine): Response
    {
        $reservation = $doctrine->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $result = $this->buildReservationQrCode($reservation);

        $response = new Response(
            $result->getString(),
            200,
            [
                'Content-Type' => $result->getMimeType(),
            ]
        );

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'reservation-' . $reservation->getId() . '-qrcode.svg'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function buildReservationQrCode(Reservation $reservation): \Endroid\QrCode\Writer\Result\ResultInterface
    {
    $detailPath = $this->generateUrl(
        'app_front_reservation_detail',
        ['id' => $reservation->getId()]
    );

    $baseUrl = rtrim((string) $this->getParameter('app.base_url'), '/');
    $detailUrl = $baseUrl . $detailPath;

    return Builder::create()
        ->writer(new PngWriter())
        ->data($detailUrl)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(ErrorCorrectionLevel::High)
        ->size(420)
        ->margin(16)
        ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
        ->build();
}


    #[Route('/reservation/{id}/qrcode/view', name: 'app_reservation_qrcode_view', methods: ['GET'])]
    public function viewReservationQrCode(int $id, ManagerRegistry $doctrine): Response
    {
        $reservation = $doctrine->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('front/reservation/qr_code_view.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/profile', name: 'app_front_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
            $user = $this->getUser();
            if (!$user) 
            {
                return $this->redirectToRoute('app_login');
            }

            if ($request->isMethod('POST')) 
            {
                $nom = $request->request->get('nom');
                $prenom = $request->request->get('prenom');
                $telephone = $request->request->get('telephone');
                $addresse = $request->request->get('addresse');

                if ($nom && method_exists($user, 'setNom')) {
                    $user->setNom($nom);
                }
                if ($prenom && method_exists($user, 'setPrenom')) {
                    $user->setPrenom($prenom);
                }
                if ($telephone && method_exists($user, 'setTelephone')) {
                    $user->setTelephone($telephone);
                }
                if ($addresse && method_exists($user, 'setAddresse')) {
                    $user->setAddresse($addresse);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Profil modifié avec succès');
                return $this->redirectToRoute('app_front_profile');
            }

            return $this->render('front/user/edit_profile.html.twig', [
                'user' => $user,
            ]);
    }

    #[Route('/change-password', name: 'app_front_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $oldPassword = $request->request->get('old_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('error', 'Ancien mot de passe incorrect');
                return $this->redirectToRoute('app_front_change_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas');
                return $this->redirectToRoute('app_front_change_password');
            }

            if (strlen((string) $newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
                return $this->redirectToRoute('app_front_change_password');
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès');
            return $this->redirectToRoute('app_front_profile');
        }

        return $this->render('front/profile/change_password.html.twig');
    }

    #[Route('/events', name: 'app_front_events', methods: ['GET'])]
    public function publicEvents(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search');
        $priceLimit = $request->query->get('price_limit');
        $page = $request->query->getInt('page', 1);
        $limit = 6;

        $qb = $entityManager->getRepository(Events::class)
            ->createQueryBuilder('e')
            ->where('e.statut != :termine')
            ->setParameter('termine', 'termine')
            ->orderBy('e.date_debut', 'ASC');

        if ($search) {
            $qb->andWhere('e.titre LIKE :search OR e.location LIKE :search OR e.categorie LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($priceLimit && is_numeric($priceLimit)) {
            $qb->andWhere('e.prix <= :priceLimit')
                ->setParameter('priceLimit', $priceLimit);
        }

        $totalEvents = $qb->select('COUNT(e.id_event)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalEvents / $limit);

        if ($page < 1) {
            $page = 1;
        }
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $limit;

        $events = $qb->select('e')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('front/event/events.html.twig', [
            'events' => $events,
            'total_events' => $totalEvents,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit,
            'search' => $search,
            'price_limit' => $priceLimit,
        ]);
    }

    #[Route('/logements', name: 'app_front_logement_index')]
    public function logements(
        Request $request,
        LogementSearchService $searchService,
        EntityManagerInterface $entityManager
     ): Response {
        $search = $request->query->get('q');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort');

        $allLogements = $searchService->searchAndSort($search, $type, $sort);
        $logements = array_filter($allLogements, function ($logement) {
            return $logement->isDisponibilite() === true;
        });

        $typesDistincts = $entityManager
            ->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->select('DISTINCT l.type')
            ->getQuery()
            ->getScalarResult();

        $typesListe = array_column($typesDistincts, 'type');

        $user = $this->getUser();
        $userId = $user instanceof User ? $user->getId() : null;

        return $this->render('front/logement/index.html.twig', [
            'logements' => $logements,
            'currentSearch' => $search,
            'currentType' => $type,
            'currentSort' => $sort,
            'allTypes' => $typesListe,
            'isConnected' => $user !== null,
            'userId' => $userId,
        ]);
    }

    #[Route('/logements/recommendations', name: 'app_front_logement_recommendations', methods: ['GET'])]
    public function recommendations(GeminiService $geminiService, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
            }

            $reservations = $em->getRepository(Reservationlog::class)
                ->createQueryBuilder('r')
                ->where('r.user = :user')
                ->andWhere('r.status IN (:statuses)')
                ->setParameter('user', $user)
                ->setParameter('statuses', ['confirmée', 'terminée'])
                ->getQuery()
                ->getResult();

            if (empty($reservations)) {
                return $this->json(['message' => 'Aucune réservation antérieure.']);
            }

            $allLogements = $em->getRepository(Logement::class)
                ->createQueryBuilder('l')
                ->where('l.disponibilite = :dispo')
                ->setParameter('dispo', true)
                ->getQuery()
                ->getResult();

            if (empty($allLogements)) {
                return $this->json(['message' => 'Aucun logement disponible.']);
            }

            $prompt = $this->buildPrompt($reservations, $allLogements);

            try {
                $responseText = $geminiService->generateRecommendations($prompt);
                $jsonString = preg_replace('/json\s*|\s*/', '', $responseText);
                $recommendations = json_decode($jsonString, true);
                $recommendedIds = $recommendations['recommended_ids'] ?? [];

                if (!empty($recommendedIds)) {
                    $recommendedLogements = $em->getRepository(Logement::class)
                        ->createQueryBuilder('l')
                        ->where('l.id IN (:ids)')
                        ->setParameter('ids', $recommendedIds)
                        ->getQuery()
                        ->getResult();
                } else {
                    $recommendedLogements = [];
                }
            } catch (\Exception $e) {
                $recommendedLogements = array_slice($allLogements, 0, 6);
            }

            if (empty($recommendedLogements)) {
                $recommendedLogements = array_slice($allLogements, 0, 6);
            }

            $html = '';
            foreach ($recommendedLogements as $logement) {
                $imageUrl = $logement->getImage() ?: '/front/pacific/images/destination-1.jpg';
                $nom = htmlspecialchars($logement->getNom() ?? '');
                $type = htmlspecialchars($logement->getType() ?? '');
                $adresse = htmlspecialchars($logement->getAdresse() ?? '');
                $adresseCourte = htmlspecialchars(substr($adresse, 0, 40));
                $capacite = $logement->getCapacite() ?? 0;
                $tarif = number_format($logement->getTarifNuit() ?? 0, 0, ',', ' ');
                $equipement = $logement->getEquipement();
                $equipementHtml = '';

                if ($equipement) {
                    $equipements = explode(',', $equipement);
                    $equipementHtml = '<div>';
                    $i = 0;
                    foreach ($equipements as $equip) {
                        if ($i < 4) {
                            $equipementHtml .= '<span class="equipement-badge">' . htmlspecialchars(trim($equip)) . '</span>';
                        } else {
                            break;
                        }
                        $i++;
                    }
                    if (count($equipements) > 4) {
                        $equipementHtml .= '<span class="equipement-badge">+' . (count($equipements) - 4) . '</span>';
                    }
                    $equipementHtml .= '</div>';
                }

                $html .= '<div class="col-md-4 ftco-animate mb-4">
                    <div class="flip-card">
                        <div class="flip-card-inner">
                            <div class="flip-card-front">
                                <div class="flip-card-front-img" style="background-image: url(\'' . $imageUrl . '\');">
                                    <div class="price-badge">' . $tarif . ' DT / nuit</div>
                                </div>
                                <div class="flip-card-front-content">
                                    <div class="flip-card-front-title">' . $nom . '</div>
                                    <div class="flip-card-front-type">' . $type . '</div>
                                    <div class="flip-card-front-location">
                                        <i class="fa fa-map-marker"></i> ' . $adresseCourte . '
                                    </div>
                                </div>
                            </div>
                            <div class="flip-card-back">
                                <div>
                                    <h3>' . $nom . '</h3>
                                    <p><i class="fa fa-users"></i> Capacité : ' . $capacite . ' personnes</p>
                                    <p><i class="fa fa-tag"></i> Type : ' . $type . '</p>
                                    <p><i class="fa fa-map-marker"></i> ' . $adresse . '</p>
                                    <p><i class="fa fa-money"></i> ' . $tarif . ' DT / nuit</p>
                                    ' . $equipementHtml . '
                                </div>
                                <button type="button" class="btn-reserver" data-id="' . $logement->getId() . '">
                                    <i class="fa fa-calendar-check-o"></i> Réserver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>';
            }

            return $this->json([
                'status' => 'completed',
                'html' => $html,
                'count' => count($recommendedLogements)
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function buildPrompt(array $reservations, array $candidates): string
    {
        $resumeReservations = '';
        foreach ($reservations as $res) {
            $log = $res->getLogement();
            $resumeReservations .= sprintf(
                "- %s (Type: %s, Capacité: %d, Prix: %.2f DT, Équipements: %s)\n",
                $log->getNom(),
                $log->getType(),
                $log->getCapacite(),
                $log->getTarifNuit(),
                $log->getEquipement() ?? 'Aucun'
            );
        }

        $resumeCandidates = '';
        foreach ($candidates as $log) {
            $resumeCandidates .= sprintf(
                "- ID: %d | %s (Type: %s, Capacité: %d, Prix: %.2f DT, Équipements: %s)\n",
                $log->getId(),
                $log->getNom(),
                $log->getType(),
                $log->getCapacite(),
                $log->getTarifNuit(),
                $log->getEquipement() ?? 'Aucun'
            );
        }

        return sprintf(
            "Tu es un assistant expert en recommandation de logements de vacances.
            Analyse l'historique des réservations de l'utilisateur et sélectionne les logements les plus pertinents parmi ceux proposés.

            ## Historique des réservations de l'utilisateur
            %s

            ## Logements disponibles (parmi lesquels choisir)
            %s

            Règles:
            - Retourne uniquement du JSON valide
            - Structure: {\"recommended_ids\": [id1, id2, ...], \"reason\": \"brève justification\"}
            - Sélectionne entre 3 et 6 logements
            - Base-toi sur le type, la capacité, le prix, les équipements et la diversité

            JSON:",
            $resumeReservations,
            $resumeCandidates
        );
    }

    private function reservationHasField(ManagerRegistry $doctrine, string $fieldName): bool
    {
        $metadata = $doctrine->getManager()->getClassMetadata(Reservation::class);
        return $metadata->hasField($fieldName);
    }
}