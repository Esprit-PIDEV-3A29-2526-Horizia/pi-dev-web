<?php
// src/Controller/LogementController.php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservationlog;
use App\Form\LogementType;
use App\Service\LogementSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\QrCodeService;
use App\Service\PdfService;


#[Route('/admin/logement', name: 'admin_logement_')]
class LogementController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        Request $request,
        LogementSearchService $searchService,
        ManagerRegistry $doctrine  // Injection du ManagerRegistry
    ): Response {
        $search = $request->query->get('search');
        $disponibilite = $request->query->get('disponibilite', 'all');
        $sort = $request->query->get('sort', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 9;

        // Nombre total de logements (avec filtres)
        $total = $searchService->countForAdmin($search, $disponibilite);

        // Récupération des logements paginés
        $logements = $searchService->searchAndSortForAdmin($search, $disponibilite, $sort, $page, $limit);

        // Nombre total de logements (sans aucun filtre, pour le badge)
        $totalLogements = $doctrine->getRepository(Logement::class)->count([]);

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
            'form' => $form->createView(),
        ]);
    }
// src/Controller/LogementController.php

#[Route('/{id}', name: 'show', methods: ['GET'])]
public function show(Logement $logement, EntityManagerInterface $em): Response
{
    $reservations = $em->getRepository(Reservationlog::class)
        ->createQueryBuilder('r')
        ->where('r.logement = :logement')
        ->setParameter('logement', $logement)
        ->orderBy('r.date_debut', 'DESC')  // ← date_debut (underscore)
        ->getQuery()
        ->getResult();

    return $this->render('admin/logement/show.html.twig', [
        'logement' => $logement,
        'reservations' => $reservations,
    ]);
}
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
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
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $logement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($logement);
            $entityManager->flush();
            $this->addFlash('success', 'Logement supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_logement_index');
    }
    #[Route('/qrcode/{id}', name: 'qrcode', methods: ['GET'])]
public function adminReservationQrcode(int $id, EntityManagerInterface $em, QrCodeService $qrCodeService): Response
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    if (!$reservation) {
        throw $this->createNotFoundException();
    }

    // Générer le contenu du QR code
    $content = "Logement: " . $reservation->getLogement()->getNom() . "\n";
    $content .= "Arrivée: " . $reservation->getDateDebut()->format('d/m/Y') . "\n";
    $content .= "Départ: " . $reservation->getDateFin()->format('d/m/Y') . "\n";
    $content .= "Adultes: " . $reservation->getAdultes() . "\n";
    $content .= "Enfants: " . $reservation->getEnfants() . "\n";
    $content .= "Chambres: " . $reservation->getNombreChambres() . "\n";
    $content .= "Pension: " . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . "\n";
    $content .= "Montant: " . number_format($reservation->getMontant(), 2, ',', ' ') . " DT\n";
    $content .= "Modalité: " . $reservation->getModalites() . "\n";
    $content .= "Statut: " . $reservation->getStatus();

    $qrCodeDataUri = $qrCodeService->generateQrCodeBase64($content);

    $html = '
    <div class="text-center">
        <img src="' . $qrCodeDataUri . '" class="img-fluid mb-3" style="max-width: 250px;">
        <h5>' . htmlspecialchars($reservation->getLogement()->getNom()) . '</h5>
        <p>' . $reservation->getDateDebut()->format('d/m/Y') . ' → ' . $reservation->getDateFin()->format('d/m/Y') . '</p>
        <p>👥 ' . $reservation->getAdultes() . ' adulte(s) + ' . $reservation->getEnfants() . ' enfant(s)</p>
        <p>🛏️ ' . $reservation->getNombreChambres() . ' chambre(s)</p>
        <p>🍽️ ' . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . '</p>
        <p>💰 ' . number_format($reservation->getMontant(), 2, ',', ' ') . ' DT</p>
        <p>💳 ' . htmlspecialchars($reservation->getModalites()) . '</p>
        <p>📌 ' . htmlspecialchars($reservation->getStatus()) . '</p>
        <a href="' . $this->generateUrl('admin_logement_download_pdf', ['id' => $reservation->getIdreslog()]) . '" class="btn btn-primary mt-2" target="_blank">
            <i class="fa fa-file-pdf"></i> Télécharger la réservation (PDF)
        </a>
    </div>';

    return new Response($html);
}

#[Route('/download-pdf/{id}', name: 'download_pdf', methods: ['GET'])]
public function adminReservationDownloadPdf(int $id, EntityManagerInterface $em, PdfService $pdfService): Response
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    if (!$reservation) {
        throw $this->createNotFoundException();
    }

    $pdfContent = $pdfService->generateReservationPdf($reservation);

    return new Response($pdfContent, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="reservation_' . $reservation->getIdreslog() . '.pdf"',
    ]);
}
}