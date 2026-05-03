<?php
// src/Controller/LogementController.php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservationlog;
use App\Form\LogementType;
use App\Service\LogementSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\QrCodeService;
use App\Service\PdfService;

#[Route('/admin/logement', name: 'admin_logement_')]
class LogementController extends AbstractController
{
    private function checkAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }

    #[Route('/', name: 'index')]
    public function index(
        Request $request,
        LogementSearchService $searchService,
        EntityManagerInterface $em
    ): Response {
        $this->checkAdminAccess();

        $search        = $request->query->get('search');
        $disponibilite = $request->query->get('disponibilite', 'all');
        $sort          = $request->query->get('sort', '');
        $page          = max(1, $request->query->getInt('page', 1));
        $limit         = 9;

        $total    = $searchService->countForAdmin(
            $search        !== null ? (string) $search        : null,
            $disponibilite !== null ? (string) $disponibilite : null
        );
        $logements = $searchService->searchAndSortForAdmin(
            $search        !== null ? (string) $search        : null,
            $disponibilite !== null ? (string) $disponibilite : null,
            $sort          !== null ? (string) $sort          : null,
            $page,
            $limit
        );

        $totalLogements = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/logement/index.html.twig', [
            'logements'      => $logements,
            'total'          => $total,
            'totalLogements' => $totalLogements,
            'currentPage'    => $page,
            'search'         => $search,
            'disponibilite'  => $disponibilite,
            'sort'           => $sort,
            'limit'          => $limit,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement, ['validation_groups' => ['Default', 'create']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($logement);
            $entityManager->flush();
            $this->addFlash('success', 'Logement ajouté avec succès.');
            return $this->redirectToRoute('admin_logement_index');
        }

        return $this->render('admin/logement/new.html.twig', [
            'logement' => $logement,
            'form'     => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Logement $logement, EntityManagerInterface $em, Request $request): Response
    {
        $this->checkAdminAccess();

        $page  = max(1, $request->query->getInt('page', 1));
        $limit = 4;

        $qb = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.logement = :logement')
            ->setParameter('logement', $logement)
            ->orderBy('r.date_debut', 'DESC');

        $total      = count($qb->getQuery()->getResult());
        $totalPages = (int) ceil($total / $limit);
        $offset     = ($page - 1) * $limit;

        $reservations = $qb->setFirstResult($offset)
                           ->setMaxResults($limit)
                           ->getQuery()
                           ->getResult();

        return $this->render('admin/logement/show.html.twig', [
            'logement'     => $logement,
            'reservations' => $reservations,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'total'        => $total,
        ]);
    }

    #[Route('/{id}/booked-dates', name: 'booked_dates', methods: ['GET'])]
    public function getBookedDates(Logement $logement, EntityManagerInterface $em): JsonResponse
    {
        $this->checkAdminAccess();

        $reservations = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.logement = :logement')
            ->andWhere('r.status NOT IN (:excluded)')
            ->setParameter('logement', $logement)
            ->setParameter('excluded', ['annulée', 'expirée'])
            ->getQuery()
            ->getResult();

        $events = [];
        foreach ($reservations as $res) {
            $start    = $res->getDateDebut()->format('Y-m-d');
            $end      = (clone $res->getDateFin())->modify('+1 day')->format('Y-m-d');
            $events[] = [
                'title'  => 'Réservé',
                'start'  => $start,
                'end'    => $end,
                'allDay' => true,
                'color'  => '#dc3545',
            ];
        }
        return $this->json($events);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Logement modifié avec succès.');
            return $this->redirectToRoute('admin_logement_index');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Erreur de validation. Vérifiez les champs.');
        }

        return $this->render('admin/logement/edit.html.twig', [
            'logement' => $logement,
            'form'     => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        if ($this->isCsrfTokenValid('delete' . $logement->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($logement);
            $entityManager->flush();
            $this->addFlash('success', 'Logement supprimé avec succès.');
        }
        return $this->redirectToRoute('admin_logement_index');
    }

    #[Route('/qrcode/{id}', name: 'qrcode', methods: ['GET'])]
    public function adminReservationQrcode(int $id, EntityManagerInterface $em, QrCodeService $qrCodeService): Response
    {
        $this->checkAdminAccess();

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException();
        }

        $logement   = $reservation->getLogement();
        $dateDebut  = $reservation->getDateDebut();
        $dateFin    = $reservation->getDateFin();
        $montant    = $reservation->getMontant() ?? 0.0;

        $content  = "Logement: " . ($logement?->getNom() ?? '') . "\n";
        $content .= "Arrivée: " . ($dateDebut?->format('d/m/Y') ?? '') . "\n";
        $content .= "Départ: " . ($dateFin?->format('d/m/Y') ?? '') . "\n";
        $content .= "Adultes: " . $reservation->getAdultes() . "\n";
        $content .= "Enfants: " . $reservation->getEnfants() . "\n";
        $content .= "Chambres: " . $reservation->getNombreChambres() . "\n";
        $content .= "Pension: " . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . "\n";
        $content .= "Montant: " . number_format($montant, 2, ',', ' ') . " DT\n";
        $content .= "Modalité: " . htmlspecialchars((string) $reservation->getModalites()) . "\n";
        $content .= "Statut: " . $reservation->getStatus();

        $qrCodeDataUri = $qrCodeService->generateQrCodeBase64($content);

        $html = '
        <div class="text-center">
            <img src="' . $qrCodeDataUri . '" class="img-fluid mb-3" style="max-width: 250px;">
            <h5>' . htmlspecialchars($logement?->getNom() ?? '') . '</h5>
            <p>' . ($dateDebut?->format('d/m/Y') ?? '') . ' → ' . ($dateFin?->format('d/m/Y') ?? '') . '</p>
            <p>👥 ' . $reservation->getAdultes() . ' adulte(s) + ' . $reservation->getEnfants() . ' enfant(s)</p>
            <p>🛏️ ' . $reservation->getNombreChambres() . ' chambre(s)</p>
            <p>🍽️ ' . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . '</p>
            <p>💰 ' . number_format($montant, 2, ',', ' ') . ' DT</p>
            <p>💳 ' . htmlspecialchars((string) $reservation->getModalites()) . '</p>
            <p>📌 ' . htmlspecialchars($reservation->getStatus() ?? '') . '</p>
            <a href="' . $this->generateUrl('admin_logement_download_pdf', ['id' => $reservation->getIdreslog()]) . '" class="btn btn-primary mt-2" target="_blank">
                <i class="fa fa-file-pdf"></i> Télécharger la réservation (PDF)
            </a>
        </div>';
        return new Response($html);
    }

    #[Route('/download-pdf/{id}', name: 'download_pdf', methods: ['GET'])]
    public function adminReservationDownloadPdf(int $id, EntityManagerInterface $em, PdfService $pdfService): Response
    {
        $this->checkAdminAccess();

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException();
        }
        $pdfContent = $pdfService->generateReservationPdf($reservation);
        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reservation_' . $reservation->getIdreslog() . '.pdf"',
        ]);
    }
}