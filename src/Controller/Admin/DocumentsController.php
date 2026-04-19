<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Service\ContratService;
use App\Service\EmailService;
use App\Service\QRCodeService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/documents')]
class DocumentsController extends AbstractController
{
    // ──────────────────────────────────────
    // 📋 LISTE des documents
    // ──────────────────────────────────────
    #[Route('/', name: 'admin_documents_index')]
    public function index(LocationRepository $locationRepository, Request $request): Response
    {
        $q         = $request->query->get('q', '');
        $locations = $locationRepository->findBy([], ['dateDebut' => 'DESC']);

        if (!empty($q)) {
            $locations = array_filter($locations, function (Location $loc) use ($q) {
                return stripos($loc->getClientNomComplet(), $q) !== false
                    || stripos($loc->getVehicule()->getImmatriculation(), $q) !== false;
            });
        }

        return $this->render('admin/documents/index.html.twig', [
            'locations' => $locations,
        ]);
    }

    // ──────────────────────────────────────
    // 📄 CONTRAT PDF
    // Utilise : ContratService::genererContratPdf(Location)
    // Les extras viennent directement de $location->getExtras()
    // ──────────────────────────────────────
    #[Route('/contrat/{id}', name: 'admin_documents_contrat', methods: ['GET'])]
    public function contrat(
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        ContratService $contratService
    ): Response {
        $pdfContent = $contratService->genererContratPdf($location);

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="contrat-HOZ-' . sprintf('%04d', $location->getIdLocation()) . '.pdf"',
            ]
        );
    }

    // ──────────────────────────────────────
    // 🧾 FACTURE PDF
    // Utilise : ContratService::genererFacturePdf(Location, float $penalites, string $notesRetour)
    // Les pénalités sont passées via paramètres GET optionnels
    // ──────────────────────────────────────
    #[Route('/facture/{id}', name: 'admin_documents_facture', methods: ['GET'])]
    public function facture(
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        ContratService $contratService,
        Request $request
    ): Response {
        // Calcul des pénalités selon les paramètres GET
        $penalites = 0.0;

        $heuresRetard = (int) $request->query->get('heures_retard', 0);
        if ($heuresRetard > 0) {
            $penalites += $heuresRetard * ContratService::PENALITE_RETARD_PAR_HEURE;
        }
        if ($request->query->getBoolean('carburant_manquant')) {
            $penalites += ContratService::PENALITE_CARBURANT_MANQUANT;
        }
        if ($request->query->getBoolean('dommages_legers')) {
            $penalites += ContratService::PENALITE_DOMMAGE_LEGER;
        }
        if ($request->query->getBoolean('dommages_graves')) {
            $penalites += ContratService::PENALITE_DOMMAGE_GRAVE;
        }

        $notesRetour = $request->query->get('notes', '');

        $pdfContent = $contratService->genererFacturePdf($location, $penalites, $notesRetour);

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="facture-HOZ-' . sprintf('%04d', $location->getIdLocation()) . '.pdf"',
            ]
        );
    }

    // ──────────────────────────────────────
    // 🔲 QR CODE
    // ──────────────────────────────────────
    #[Route('/qrcode/{id}', name: 'admin_documents_qrcode')]
    public function qrcode(
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        QRCodeService $qrCodeService
    ): Response {
        $qrCode = $qrCodeService->genererQRCodeLocation(
            $location->getIdLocation(),
            $location->getClientNomComplet(),
            $location->getVehicule()->getImmatriculation(),
            $location->getDateDebut()->format('d/m/Y'),
            $location->getDateFinPrevue()->format('d/m/Y')
        );

        return $this->render('admin/documents/qrcode.html.twig', [
            'location' => $location,
            'qrCode'   => $qrCode,
        ]);
    }

    // ──────────────────────────────────────
    // 📧 ENVOYER CONTRAT PAR EMAIL
    // ──────────────────────────────────────
    #[Route('/envoyer-contrat/{id}', name: 'admin_documents_envoyer_contrat', methods: ['POST'])]
    public function envoyerContrat(
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        Request $request,
        EmailService $emailService
    ): Response {
        $email = $request->request->get('email');
        if (empty($email)) {
            $this->addFlash('error', 'Veuillez saisir une adresse email.');
            return $this->redirectToRoute('admin_documents_index');
        }

        $resultat = $emailService->envoyerContrat($location, $email);
        $this->addFlash($resultat['succes'] ? 'success' : 'error', $resultat['message']);

        return $this->redirectToRoute('admin_documents_index');
    }

    // ──────────────────────────────────────
    // 📧 ENVOYER FACTURE PAR EMAIL
    // ──────────────────────────────────────
    #[Route('/envoyer-facture/{id}', name: 'admin_documents_envoyer_facture', methods: ['POST'])]
    public function envoyerFacture(
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        Request $request,
        EmailService $emailService
    ): Response {
        $email = $request->request->get('email');
        if (empty($email)) {
            $this->addFlash('error', 'Veuillez saisir une adresse email.');
            return $this->redirectToRoute('admin_documents_index');
        }

        $montantTotal = (float) $location->getMontantTotal();
        $avance       = (float) $location->getAvance();
        $soldeRestant = $location->getResteAPayer();

        $resultat = $emailService->envoyerFactureFinale($location, $email, $montantTotal, $avance, $soldeRestant);
        $this->addFlash($resultat['succes'] ? 'success' : 'error', $resultat['message']);

        return $this->redirectToRoute('admin_documents_index');
    }
}