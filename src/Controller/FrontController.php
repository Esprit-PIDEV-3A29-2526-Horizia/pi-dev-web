<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Voyage;
use App\Service\CurrencyService;
use App\Service\OpenWeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
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

        $voyages = $entityManager->createQueryBuilder()
            ->select('v', 'COUNT(r.id) AS HIDDEN nbReservations')
            ->from(Voyage::class, 'v')
            ->leftJoin(Reservation::class, 'r', 'WITH', 'r.voyage = v AND r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->groupBy('v.id')
            ->orderBy('nbReservations', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

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

    #[Route('/voyages', name: 'app_front_voyages', methods: ['GET'])]
    public function voyages(
        ManagerRegistry $doctrine,
        Request $request,
        OpenWeatherService $openWeatherService,
        CurrencyService $currencyService
    ): Response {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 6;
        $offset = ($page - 1) * $limit;

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

        $countQb = clone $qb;
        $totalVoyages = (int) $countQb
            ->select('COUNT(v.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalVoyages / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $voyages = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $weatherData = [];
        $convertedPrices = [];
        $currency = $filters['currency'];

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

        return $this->render('front/voyages.html.twig', [
            'voyages' => $voyages,
            'weatherData' => $weatherData,
            'convertedPrices' => $convertedPrices,
            'currency' => $currency,
            'currencySymbol' => $currencyService->getSymbol($currency),
            'allowedCurrencies' => $currencyService->getAllowedCurrencies(),
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalVoyages' => $totalVoyages,
            'limit' => $limit,
        ]);
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

        return $this->render('front/detail.html.twig', [
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
            ]);
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

            // Toujours enregistrer en DT dans la base
            $reservation = new Reservation();
            $reservation->setVoyage($voyage);
            $reservation->setDateReservation(new \DateTime());
            $reservation->setStatut('EN_ATTENTE');

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
                $reservation->setPrixTotal($prixTotalDt);
            }

            $user = $this->getUser();
            if ($user instanceof User) {
                $reservation->setUser($user);
            }

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de réservation a bien été enregistrée.');

            return $this->redirectToRoute('app_front_voyage_detail', [
                'id' => $voyage->getId(),
                'currency' => $currency,
            ]);
        }

        return $this->render('front/reservation.html.twig', [
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
    public function mesReservations(ManagerRegistry $doctrine, Request $request): Response
    {
        $selectedStatut = trim((string) $request->query->get('statut', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 6;
        $offset = ($page - 1) * $limit;

        $qb = $doctrine->getRepository(Reservation::class)->createQueryBuilder('r')
            ->leftJoin('r.voyage', 'v')
            ->addSelect('v')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->orderBy('r.dateReservation', 'DESC');

        $user = $this->getUser();
        if ($user instanceof User) {
            $qb->andWhere('r.user = :user')
                ->setParameter('user', $user);
        }

        if ($selectedStatut !== '') {
            $qb->andWhere('r.statut = :statut')
                ->setParameter('statut', $selectedStatut);
        }

        $countQb = clone $qb;
        $totalReservations = (int) $countQb
            ->select('COUNT(r.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalReservations / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $reservations = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('front/mes_reservations.html.twig', [
            'reservations' => $reservations,
            'selectedStatut' => $selectedStatut,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalReservations' => $totalReservations,
        ]);
    }

    #[Route('/reservation/{id}/annuler', name: 'app_front_annuler_reservation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annulerReservation(
        int $id,
        ManagerRegistry $doctrine,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $doctrine->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $user = $this->getUser();
        if ($user instanceof User && $reservation->getUser() && $reservation->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette réservation.');
        }

        if ($reservation->getStatut() === 'ANNULEE') {
            $this->addFlash('error', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        if ($reservation->getStatut() === 'CONFIRMEE') {
            $voyage = $reservation->getVoyage();

            if ($voyage) {
                $voyage->setPlacesRestantes($voyage->getPlacesRestantes() + $reservation->getNbrPersonnes());
                $entityManager->persist($voyage);
            }
        }

        $reservation->setStatut('ANNULEE');
        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'La réservation a bien été annulée.');

        return $this->redirectToRoute('app_front_mes_reservations');
    }
}