<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Voyage;
use App\Service\CurrencyService;
use App\Service\OpenWeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        CurrencyService $currencyService,
        PaginatorInterface $paginator
    ): Response {
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
        $currency = $request->query->get('currency', 'TND');
        $selectedStatut = trim((string) $request->query->get('statut', ''));
        $page = $request->query->getInt('page', 1);

        $qb = $doctrine->getRepository(Reservation::class)->createQueryBuilder('r')
            ->leftJoin('r.voyage', 'v')
            ->addSelect('v')
            ->orderBy('r.dateReservation', 'DESC');

        if ($selectedStatut !== '') {
            $qb->andWhere('r.statut = :statut')
                ->setParameter('statut', $selectedStatut);
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

        return $this->render('front/reservation/reservation_detail.html.twig', [
            'reservation' => $reservation,
            'currency' => 'TND',
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

        $paymentStatus = method_exists($reservation, 'getPaymentStatus')
            ? strtoupper((string) $reservation->getPaymentStatus())
            : 'NON_PAYEE';

        if ($reservation->getStatut() === 'ANNULEE') {
            $this->addFlash('error', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        if ($reservation->getStatut() === 'CONFIRMEE') {
            $this->addFlash('error', 'Une réservation confirmée ne peut pas être annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        if ($paymentStatus === 'PAYEE') {
            $this->addFlash('error', 'Une réservation payée ne peut pas être annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        $reservation->setStatut('ANNULEE');
        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'La réservation a bien été annulée.');

        return $this->redirectToRoute('app_front_mes_reservations');
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

    private function buildReservationQrCode(Reservation $reservation)
    {
        $detailPath = $this->generateUrl(
            'app_front_reservation_detail',
            ['id' => $reservation->getId()]
        );

        $baseUrl = 'http://192.168.1.13:8000'; 
        $detailUrl = $baseUrl . $detailPath;

        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/logo.png';

        $builder = Builder::create()
            ->writer(new SvgWriter())
            ->data($detailUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(420)
            ->margin(16)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->labelText('Horozia - Réservation #' . $reservation->getId());

        if (file_exists($logoPath)) {
            $builder
                ->logoPath($logoPath)
                ->logoResizeToWidth(80)
                ->logoPunchoutBackground(true);
        }

        return $builder->build();
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


}