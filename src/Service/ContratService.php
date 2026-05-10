<?php

namespace App\Service;

use App\Entity\Location;
use DateTime;
use Dompdf\Dompdf;
use Dompdf\Options;

class ContratService
{
    // Informations de l'agence
    private const NOM_AGENCE     = 'Horizia';
    private const ADRESSE_AGENCE = 'Tunis, Tunisie';
    private const TEL_AGENCE     = '+216 53 661 445';
    private const EMAIL_AGENCE   = 'horizia.agence@gmail.com';
    private const MF_AGENCE      = 'MF: XXXXXXXX/A/M/000';

    // Pénalités
    public const PENALITE_RETARD_PAR_HEURE  = 5.0;
    public const PENALITE_CARBURANT_MANQUANT = 20.0;
    public const PENALITE_DOMMAGE_LEGER      = 50.0;
    public const PENALITE_DOMMAGE_GRAVE      = 200.0;

    // Labels des extras (doit correspondre aux clés dans le controller)
    private const EXTRAS_LABELS = [
        'siege_bebe'                => 'Siège bébé',
        'gps'                       => 'GPS',
        'plein_carburant'           => 'Plein carburant',
        'conducteur_supplementaire' => 'Conducteur supplémentaire',
        'wifi'                      => 'WiFi embarqué',
        'assurance_complementaire'  => 'Assurance complémentaire',
    ];

    // ──────────────────────────────────────
    // Calculs utilitaires
    // ──────────────────────────────────────

    public function calculerNbJours(\DateTimeInterface $debut, \DateTimeInterface $fin): int
    {
        $jours = (int) $debut->diff($fin)->days;
        return $jours <= 0 ? 1 : $jours;
    }

    public function calculerMontantBase(float $prixParJour, \DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        return $prixParJour * $this->calculerNbJours($debut, $fin);
    }

    public function calculerSolde(float $total, float $avancePayee): float
    {
        return max(0, $total - $avancePayee);
    }

    public function formaterMontant(float $montant): string
    {
        return number_format($montant, 3, ',', ' ') . ' TND';
    }

    // ──────────────────────────────────────
    // 📄 Générer le contrat en PDF
    // ──────────────────────────────────────

    public function genererContratPdf(Location $location): string
    {
        $html = $this->buildContratHtml($location);
        return $this->htmlToPdf($html);
    }

    // ──────────────────────────────────────
    // 📄 Générer la facture finale en PDF
    // ──────────────────────────────────────

    public function genererFacturePdf(Location $location, float $penalites = 0.0, string $notesRetour = ''): string
    {
        $html = $this->buildFactureHtml($location, $penalites, $notesRetour);
        return $this->htmlToPdf($html);
    }

    // ──────────────────────────────────────
    // 🔧 Conversion HTML → PDF via DomPDF
    // ──────────────────────────────────────

    private function htmlToPdf(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // ──────────────────────────────────────
    // 🏗️ HTML du contrat
    // ──────────────────────────────────────

    private function buildContratHtml(Location $location): string
    {
       $debut   = $location->getDateDebut() ?? new \DateTime();
$fin     = $location->getDateFinPrevue() ?? new \DateTime();
$nbJours = $this->calculerNbJours($debut, $fin);
        $prixJour = (float) $location->getPrixParJour();
        $montantBase  = $prixJour * $nbJours;
        $extrasTotal  = $location->getExtrasTotal();
        $montantTotal = (float) $location->getMontantTotal();
        $avance       = (float) $location->getAvance();
        $solde        = $this->calculerSolde($montantTotal, $avance);

        $extrasHtml = '';
        if ($location->getExtras()) {
            foreach ($location->getExtras() as $cle => $prix) {
                $label = self::EXTRAS_LABELS[$cle] ?? $cle;
                $extrasHtml .= '
                <tr>
                    <td style="padding:6px 10px;border-bottom:1px solid #eee;">+ ' . htmlspecialchars($label) . '</td>
                    <td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;">' . number_format((float)$prix, 3, ',', ' ') . ' TND</td>
                </tr>';
            }
        }

        $vehicule = $location->getVehicule();
        $modeleNom = '';
        if ($vehicule && $vehicule->getModele()) {
            $modeleNom = ($vehicule->getModele()->getMarque()?->getNomMarque() ?? '') . ' '
                       . $vehicule->getModele()->getNomModele();
        }

        return '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2d3d; margin: 0; padding: 0; }
  .header { background: #23779C; color: white; padding: 24px 32px; }
  .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
  .header p  { margin: 4px 0 0; font-size: 11px; opacity: .85; }
  .badge-contrat { background: rgba(255,255,255,.2); padding: 4px 12px; border-radius: 20px; font-size: 11px; float: right; margin-top: -30px; }
  .body { padding: 24px 32px; }
  .section { margin-bottom: 20px; }
  .section-title { background: #f0f4f8; padding: 7px 12px; border-left: 4px solid #23779C; font-weight: bold; font-size: 12px; margin-bottom: 10px; color: #23779C; }
  table.info { width: 100%; border-collapse: collapse; }
  table.info td { padding: 5px 8px; font-size: 11px; }
  table.info td:first-child { color: #6c757d; width: 40%; }
  table.info td:last-child { font-weight: 600; }
  table.fin { width: 100%; border-collapse: collapse; }
  table.fin tr:last-child td { font-weight: 800; font-size: 14px; color: #23779C; border-top: 2px solid #23779C; padding-top: 8px; }
  .conditions { font-size: 10px; color: #6c757d; line-height: 1.7; }
  .conditions li { margin-bottom: 3px; }
  .footer { background: #f0f4f8; padding: 14px 32px; text-align: center; font-size: 10px; color: #6c757d; margin-top: 20px; }
  .signatures { margin-top: 30px; }
  .sig-box { display: inline-block; width: 45%; border-top: 1px solid #333; padding-top: 6px; font-size: 11px; text-align: center; }
  .total-row td { background: #e8f4fb; font-weight: 800; color: #23779C; }
</style>
</head>
<body>

<div class="header">
  <h1>HORIZIA &mdash; CONTRAT DE LOCATION</h1>
  <p>Votre partenaire de mobilité en Tunisie</p>
  <div class="badge-contrat">N° HOZ-' . sprintf('%04d', $location->getIdLocation()) . '</div>
</div>

<div class="body">

  <div style="display:table;width:100%;margin-bottom:16px;">
    <div style="display:table-cell;font-size:11px;color:#6c757d;">
      Date d&apos;émission : <strong>' . (new DateTime())->format('d/m/Y') . '</strong>
    </div>
  </div>

  <!-- CLIENT -->
  <div class="section">
    <div class="section-title">&#128100; Informations client</div>
    <table class="info">
      <tr><td>Nom complet</td><td>' . htmlspecialchars($location->getClientNomComplet() ?? '—') . '</td></tr>
      <tr><td>Téléphone</td><td>' . htmlspecialchars($location->getClientTelephone() ?? '—') . '</td></tr>
      <tr><td>CIN</td><td>' . htmlspecialchars($location->getClientCin() ?? '—') . '</td></tr>
      <tr><td>N° Permis</td><td>' . htmlspecialchars($location->getClientPermisNumero() ?? '—') . '</td></tr>
      <tr><td>Adresse</td><td>' . htmlspecialchars(($location->getClientAdresse() ?? '') . ' ' . ($location->getClientVille() ?? '')) . '</td></tr>
    </table>
  </div>

  <!-- VEHICULE -->
  <div class="section">
    <div class="section-title">&#128663; Informations véhicule</div>
    <table class="info">
      <tr><td>Immatriculation</td><td>' . htmlspecialchars($vehicule?->getImmatriculation() ?? '—') . '</td></tr>
      <tr><td>Modèle</td><td>' . htmlspecialchars($modeleNom ?: '—') . '</td></tr>
      <tr><td>Kilométrage départ</td><td>' . number_format((int)$location->getKilometrageDebut(), 0, ',', ' ') . ' km</td></tr>
    </table>
  </div>

  <!-- PERIODE -->
  <div class="section">
    <div class="section-title">&#128197; Période de location</div>
    <table class="info">
      <tr><td>Date début</td><td>' . $debut->format('d/m/Y H:i') . '</td></tr>
      <tr><td>Date fin prévue</td><td>' . $fin->format('d/m/Y H:i') . '</td></tr>
      <tr><td>Durée</td><td>' . $nbJours . ' jour(s)</td></tr>
    </table>
  </div>

  <!-- FINANCIER -->
  <div class="section">
    <div class="section-title">&#128179; Détail financier</div>
    <table class="fin">
      <tr>
        <td style="padding:6px 10px;border-bottom:1px solid #eee;">Location (' . $nbJours . ' j × ' . number_format($prixJour, 3, ',', ' ') . ' TND)</td>
        <td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;">' . number_format($montantBase, 3, ',', ' ') . ' TND</td>
      </tr>
      ' . $extrasHtml . '
      <tr class="total-row">
        <td style="padding:8px 10px;">MONTANT TOTAL</td>
        <td style="padding:8px 10px;text-align:right;">' . number_format($montantTotal, 3, ',', ' ') . ' TND</td>
      </tr>
      <tr>
        <td style="padding:6px 10px;color:#6c757d;">Avance versée</td>
        <td style="padding:6px 10px;text-align:right;color:#28a745;">' . number_format($avance, 3, ',', ' ') . ' TND</td>
      </tr>
      <tr>
        <td style="padding:6px 10px;font-weight:700;">Reste à payer</td>
        <td style="padding:6px 10px;text-align:right;font-weight:700;color:#dc3545;">' . number_format($solde, 3, ',', ' ') . ' TND</td>
      </tr>
    </table>
  </div>

  <!-- CONDITIONS -->
  <div class="section">
    <div class="section-title">&#128209; Conditions générales</div>
    <ul class="conditions">
      <li>Le véhicule doit être restitué dans l&apos;état de sa prise en charge.</li>
      <li>Tout retard non signalé sera facturé 5 TND/heure.</li>
      <li>Le client est responsable des infractions et amendes.</li>
      <li>Le carburant est à la charge du client (sauf option plein carburant).</li>
      <li>Tout dommage sera facturé selon le barème de l&apos;agence.</li>
    </ul>
  </div>

  <!-- SIGNATURES -->
  <div class="signatures">
    <table style="width:100%;">
      <tr>
        <td style="width:45%;text-align:center;padding-top:40px;border-top:1px solid #333;">Signature du client</td>
        <td style="width:10%;"></td>
        <td style="width:45%;text-align:center;padding-top:40px;border-top:1px solid #333;">Signature de l&apos;agent</td>
      </tr>
    </table>
  </div>

</div>

<div class="footer">
  ' . self::NOM_AGENCE . ' &bull; ' . self::TEL_AGENCE . ' &bull; ' . self::EMAIL_AGENCE . '<br>
  ' . self::ADRESSE_AGENCE . ' &bull; ' . self::MF_AGENCE . '
</div>

</body>
</html>';
    }

    // ──────────────────────────────────────
    // 🏗️ HTML de la facture finale
    // ──────────────────────────────────────

    private function buildFactureHtml(Location $location, float $penalites, string $notesRetour): string
    {
        $montantBase  = (float) $location->getMontantTotal();
        $avance       = (float) $location->getAvance();
        $total        = $montantBase + $penalites;
        $solde        = $this->calculerSolde($total, $avance);

        $penalitesHtml = $penalites > 0
            ? '<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;color:#dc3545;">Pénalités</td><td style="padding:6px 10px;text-align:right;color:#dc3545;">+' . number_format($penalites, 3, ',', ' ') . ' TND</td></tr>'
            : '';

        $notesHtml = !empty($notesRetour)
            ? '<div class="section"><div class="section-title">Notes de retour</div><p style="font-size:11px;">' . htmlspecialchars($notesRetour) . '</p></div>'
            : '';

        return '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2d3d; margin: 0; }
  .header { background: #23779C; color: white; padding: 24px 32px; }
  .header h1 { margin: 0; font-size: 22px; }
  .header p { margin: 4px 0 0; font-size: 11px; opacity:.85; }
  .body { padding: 24px 32px; }
  .section { margin-bottom: 20px; }
  .section-title { background: #f0f4f8; padding: 7px 12px; border-left: 4px solid #23779C; font-weight: bold; font-size: 12px; margin-bottom: 10px; color: #23779C; }
  table.info td { padding: 5px 8px; font-size: 11px; }
  table.info td:first-child { color: #6c757d; width: 40%; }
  .total-row td { background: #e8f4fb; font-weight: 800; color: #23779C; font-size:14px; }
  .footer { background: #f0f4f8; padding: 14px 32px; text-align: center; font-size: 10px; color: #6c757d; margin-top: 20px; }
</style>
</head>
<body>
<div class="header">
  <h1>HORIZIA &mdash; FACTURE FINALE</h1>
  <p>N° FAC-' . sprintf('%04d', $location->getIdLocation()) . ' &bull; Date retour : ' . (new DateTime())->format('d/m/Y') . '</p>
</div>
<div class="body">
  <div class="section">
    <div class="section-title">&#128100; Client</div>
    <table class="info" style="width:100%;border-collapse:collapse;">
      <tr><td>Nom</td><td>' . htmlspecialchars($location->getClientNomComplet() ?? '—') . '</td></tr>
      <tr><td>Téléphone</td><td>' . htmlspecialchars($location->getClientTelephone() ?? '—') . '</td></tr>
      <tr><td>CIN</td><td>' . htmlspecialchars($location->getClientCin() ?? '—') . '</td></tr>
      <tr><td>Véhicule</td><td>' . htmlspecialchars($location->getVehicule()?->getImmatriculation() ?? '—') . '</td></tr>
<tr><td>Période</td><td>' . ($location->getDateDebut()?->format('d/m/Y') ?? '—') . ' → ' . ($location->getDateFinPrevue()?->format('d/m/Y') ?? '—') . '</td></tr>    </table>
  </div>
  <div class="section">
    <div class="section-title">&#128179; Récapitulatif financier</div>
    <table style="width:100%;border-collapse:collapse;">
      <tr>
        <td style="padding:6px 10px;border-bottom:1px solid #eee;">Montant location</td>
        <td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;">' . number_format($montantBase, 3, ',', ' ') . ' TND</td>
      </tr>
      ' . $penalitesHtml . '
      <tr class="total-row">
        <td style="padding:8px 10px;">TOTAL GÉNÉRAL</td>
        <td style="padding:8px 10px;text-align:right;">' . number_format($total, 3, ',', ' ') . ' TND</td>
      </tr>
      <tr>
        <td style="padding:6px 10px;color:#6c757d;">Avance versée</td>
        <td style="padding:6px 10px;text-align:right;color:#28a745;">' . number_format($avance, 3, ',', ' ') . ' TND</td>
      </tr>
      <tr>
        <td style="padding:6px 10px;font-weight:700;">MONTANT À PAYER</td>
        <td style="padding:6px 10px;text-align:right;font-weight:700;color:#dc3545;">' . number_format($solde, 3, ',', ' ') . ' TND</td>
      </tr>
    </table>
  </div>
  ' . $notesHtml . '
  <p style="text-align:center;font-style:italic;margin-top:30px;">Merci de votre confiance ! À bientôt chez Horizia.</p>
</div>
<div class="footer">
  ' . self::NOM_AGENCE . ' &bull; ' . self::TEL_AGENCE . ' &bull; ' . self::EMAIL_AGENCE . '<br>' . self::ADRESSE_AGENCE . '
</div>
</body>
</html>';
    }
}