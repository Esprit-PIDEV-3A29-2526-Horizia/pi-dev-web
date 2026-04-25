<?php

namespace App\Service;

use App\Entity\Location;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Exception;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    
    // Configuration de l'agence
    private const EMAIL_EXPEDITEUR = 'arouayadam@gmail.com';
    private const NOM_AGENCE = 'Horizia - Agence de Location';
    private const TEL_AGENCE = '+216 53 661 445';
    private const ADRESSE_AGENCE = 'Tunis, Tunisie';

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * Envoie un email de confirmation de location
     */
    public function envoyerConfirmationLocation(Location $location, string $emailClient): array
    {
        if (empty($emailClient)) {
            return ['succes' => false, 'message' => 'Adresse email du client manquante'];
        }

        $sujet = '✅ Confirmation de votre location - Horizia #' . $location->getIdLocation();
        $corpsHtml = $this->buildEmailConfirmation($location);
        
        return $this->envoyerEmail($emailClient, $sujet, $corpsHtml);
    }

    /**
     * Envoie un email de rappel (24h avant)
     */
    public function envoyerRappelLocation(Location $location, string $emailClient): array
    {
        if (empty($emailClient)) {
            return ['succes' => false, 'message' => 'Adresse email du client manquante'];
        }

        $sujet = '⏰ Rappel : Votre location commence demain - Horizia #' . $location->getIdLocation();
        $corpsHtml = $this->buildEmailRappel($location);
        
        return $this->envoyerEmail($emailClient, $sujet, $corpsHtml);
    }

    /**
     * Envoie la facture finale
     */
    public function envoyerFactureFinale(Location $location, string $emailClient, float $montantTotal, float $avancePayee, float $soldeRestant): array
    {
        if (empty($emailClient)) {
            return ['succes' => false, 'message' => 'Adresse email du client manquante'];
        }

        $sujet = '🧾 Votre facture finale - Horizia Location #' . $location->getIdLocation();
        $corpsHtml = $this->buildEmailFacture($location, $montantTotal, $avancePayee, $soldeRestant);
        
        return $this->envoyerEmail($emailClient, $sujet, $corpsHtml);
    }

    /**
     * Envoie le contrat de location
     */
    public function envoyerContrat(Location $location, string $emailClient): array
    {
        if (empty($emailClient)) {
            return ['succes' => false, 'message' => 'Adresse email du client manquante'];
        }

        $sujet = '📄 Votre contrat de location - Horizia #' . $location->getIdLocation();
        $corpsHtml = $this->buildEmailContrat($location);
        
        return $this->envoyerEmail($emailClient, $sujet, $corpsHtml);
    }

    /**
     * Méthode centrale d'envoi d'email
     */
    private function envoyerEmail(string $destinataire, string $sujet, string $corpsHtml): array
    {
        try {
            $email = (new Email())
                ->from(self::EMAIL_EXPEDITEUR)
                ->to($destinataire)
                ->subject($sujet)
                ->html($corpsHtml);

            $this->mailer->send($email);
            
            $this->logger->info('Email envoyé à : ' . $destinataire);
            return ['succes' => true, 'message' => 'Email envoyé avec succès à ' . $destinataire];

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email : ' . $e->getMessage());
            return ['succes' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    /**
     * Template email de confirmation
     */
    private function buildEmailConfirmation(Location $location): string
    {
        $clientNom = $location->getClientNomComplet() ?? 'Client';
        $dateDebut = $location->getDateDebut()?->format('d/m/Y') ?? '—';
        $dateFin = $location->getDateFinPrevue()?->format('d/m/Y') ?? '—';
        
        return $this->getEmailLayout(
            '✅ Confirmation de Location',
            '#27ae60',
            "<p style='font-size:16px; color:#2c3e50;'>Bonjour <strong>{$clientNom}</strong>,</p>
             <p>Nous vous confirmons votre réservation de véhicule chez <strong>Horizia</strong>.</p>",
            $this->buildTableDetails($location),
            "<p style='color:#7f8c8d;'>Veuillez vous présenter muni de votre CIN et permis de conduire.</p>
             <p>Merci de votre confiance !</p>"
        );
    }

    /**
     * Template email de rappel
     */
    private function buildEmailRappel(Location $location): string
    {
        $clientNom = $location->getClientNomComplet() ?? 'Client';
        
        return $this->getEmailLayout(
            '⏰ Rappel de Location',
            '#f39c12',
            "<p style='font-size:16px; color:#2c3e50;'>Bonjour <strong>{$clientNom}</strong>,</p>
             <p>Nous vous rappelons que votre location commence <strong>demain</strong>.</p>",
            $this->buildTableDetails($location),
            "<p style='color:#7f8c8d;'>N'oubliez pas votre CIN et permis de conduire.</p>
             <p>À demain chez Horizia !</p>"
        );
    }

    /**
     * Template email facture
     */
    private function buildEmailFacture(Location $location, float $total, float $avance, float $solde): string
    {
        $clientNom = $location->getClientNomComplet() ?? 'Client';
        
        $tableFinance = "
            <table style='width:100%; border-collapse:collapse; margin:15px 0;'>
                <tr style='background:#f8f9fa;'>
                    <td style='padding:10px; border:1px solid #dee2e6;'>Montant total</td>
                    <td style='padding:10px; border:1px solid #dee2e6; font-weight:bold;'>" . number_format($total, 3) . " TND</td>
                </tr>
                <tr>
                    <td style='padding:10px; border:1px solid #dee2e6;'>Avance payée</td>
                    <td style='padding:10px; border:1px solid #dee2e6; color:#27ae60;'>" . number_format($avance, 3) . " TND</td>
                </tr>
                <tr style='background:#fff3cd;'>
                    <td style='padding:10px; border:1px solid #dee2e6; font-weight:bold;'>Solde restant</td>
                    <td style='padding:10px; border:1px solid #dee2e6; font-weight:bold; color:#e74c3c;'>" . number_format($solde, 3) . " TND</td>
                </tr>
            </table>
        ";
        
        return $this->getEmailLayout(
            '🧾 Facture Finale',
            '#8e44ad',
            "<p style='font-size:16px; color:#2c3e50;'>Bonjour <strong>{$clientNom}</strong>,</p>
             <p>Merci d'avoir choisi Horizia. Voici votre facture finale.</p>",
            $this->buildTableDetails($location) . $tableFinance,
            "<p>Merci pour votre confiance. À bientôt !</p>"
        );
    }

    /**
     * Template email contrat
     */
    private function buildEmailContrat(Location $location): string
    {
        $clientNom = $location->getClientNomComplet() ?? 'Client';
        
        return $this->getEmailLayout(
            '📄 Votre Contrat de Location',
            '#2980b9',
            "<p style='font-size:16px; color:#2c3e50;'>Bonjour <strong>{$clientNom}</strong>,</p>
             <p>Veuillez trouver ci-dessous les détails de votre contrat de location.</p>",
            $this->buildTableDetails($location),
            "<p style='color:#7f8c8d;'>Ce contrat fait foi de votre accord avec Horizia.</p>
             <p>Conditions générales disponibles en agence.</p>"
        );
    }

    /**
     * Tableau des détails de location
     */
    private function buildTableDetails(Location $location): string
    {
        $dateDebut = $location->getDateDebut()?->format('d/m/Y') ?? '—';
        $dateFin = $location->getDateFinPrevue()?->format('d/m/Y') ?? '—';
        $prixJour = number_format((float) $location->getPrixParJour(), 3);
        
        return "
            <table style='width:100%; border-collapse:collapse; margin:15px 0;'>
                <tr style='background:#3498db; color:white;'>
                    <th colspan='2' style='padding:12px; text-align:left;'>📋 Détails de la Location #{$location->getIdLocation()}</th>
                </tr>
                <tr><td style='padding:10px; border:1px solid #dee2e6;'>Client</td>
                    <td style='padding:10px; border:1px solid #dee2e6; font-weight:bold;'>{$location->getClientNomComplet()}</td>
                </tr>
                <tr style='background:#f8f9fa;'><td style='padding:10px; border:1px solid #dee2e6;'>Téléphone</td>
                    <td style='padding:10px; border:1px solid #dee2e6;'>{$location->getClientTelephone()}</td>
                </tr>
                <tr><td style='padding:10px; border:1px solid #dee2e6;'>Véhicule</td>
                    <td style='padding:10px; border:1px solid #dee2e6; font-weight:bold;'>{$location->getVehicule()->getImmatriculation()}</td>
                </tr>
                <tr style='background:#f8f9fa;'><td style='padding:10px; border:1px solid #dee2e6;'>Date début</td>
                    <td style='padding:10px; border:1px solid #dee2e6;'>{$dateDebut}</td>
                </tr>
                <tr><td style='padding:10px; border:1px solid #dee2e6;'>Date fin prév.</td>
                    <td style='padding:10px; border:1px solid #dee2e6;'>{$dateFin}</td>
                </tr>
                <tr style='background:#f8f9fa;'><td style='padding:10px; border:1px solid #dee2e6;'>Prix/jour</td>
                    <td style='padding:10px; border:1px solid #dee2e6; color:#27ae60; font-weight:bold;'>{$prixJour} TND</td>
                </tr>
                <tr><td style='padding:10px; border:1px solid #dee2e6;'>Statut</td>
                    <td style='padding:10px; border:1px solid #dee2e6;'>{$location->getStatut()}</td>
                </tr>
            </table>
        ";
    }

    /**
     * Envoie un email d'approbation d'annulation
     */
    public function sendCancellationApprovedEmail(Location $location, string $emailClient): array
    {
        if (empty($emailClient)) {
            return ['succes' => false, 'message' => 'Adresse email du client manquante'];
        }

        $sujet = '✅ Votre demande d\'annulation a été approuvée - Horizia #' . $location->getIdLocation();
        $clientNom = $location->getClientNomComplet() ?? 'Client';

        $corpsHtml = $this->getEmailLayout(
            '✅ Annulation Approuvée',
            '#27ae60',
            "<p style='font-size:16px; color:#2c3e50;'>Bonjour <strong>{$clientNom}</strong>,</p>
             <p>Votre demande d'annulation pour la location <strong>#{$location->getIdLocation()}</strong> a été <strong>approuvée</strong>.</p>",
            $this->buildTableDetails($location),
            "<p style='color:#7f8c8d;'>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
             <p>Merci pour votre compréhension.</p>"
        );

        return $this->envoyerEmail($emailClient, $sujet, $corpsHtml);
    }

    /**
     * Envoie un email de refus d'annulation
     */
    public function sendCancellationRejectedEmail(Location $location, string $emailClient): array
    {
        if (empty($emailClient)) {
            return ['succes' => false, 'message' => 'Adresse email du client manquante'];
        }

        $sujet = '❌ Votre demande d\'annulation a été refusée - Horizia #' . $location->getIdLocation();
        $clientNom = $location->getClientNomComplet() ?? 'Client';

        $corpsHtml = $this->getEmailLayout(
            '❌ Annulation Refusée',
            '#e74c3c',
            "<p style='font-size:16px; color:#2c3e50;'>Bonjour <strong>{$clientNom}</strong>,</p>
             <p>Votre demande d'annulation pour la location <strong>#{$location->getIdLocation()}</strong> a été <strong>refusée</strong>.</p>",
            $this->buildTableDetails($location),
            "<p style='color:#7f8c8d;'>Votre location reste confirmée aux dates prévues.</p>
             <p>Pour toute question, contactez-nous directement en agence.</p>"
        );

        return $this->envoyerEmail($emailClient, $sujet, $corpsHtml);
    }

    /**
     * Layout principal des emails
     */
    private function getEmailLayout(string $titre, string $couleur, string $intro, string $contenu, string $footer): string
    {
        return "
            <!DOCTYPE html>
            <html>
            <body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>
                <table width='100%' style='max-width:600px;margin:30px auto;'>
                    <tr>
                        <td style='background:{$couleur};padding:30px;text-align:center;border-radius:10px 10px 0 0;'>
                            <h1 style='color:white;margin:0;font-size:26px;'>🚗 HORIZIA</h1>
                            <p style='color:rgba(255,255,255,0.85);margin:8px 0 0;'>Agence de Location de Voitures</p>
                            <h2 style='color:white;margin:15px 0 0;font-size:20px;'>{$titre}</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style='background:white;padding:30px;'>
                            {$intro}
                            {$contenu}
                            {$footer}
                        </td>
                    </tr>
                    <tr>
                        <td style='background:#2c3e50;padding:20px;text-align:center;border-radius:0 0 10px 10px;'>
                            <p style='color:rgba(255,255,255,0.7);margin:0;font-size:13px;'>
                                📍 " . self::ADRESSE_AGENCE . " | 📞 " . self::TEL_AGENCE . " | 📧 " . self::EMAIL_EXPEDITEUR . "
                            </p>
                            <p style='color:rgba(255,255,255,0.5);margin:8px 0 0;font-size:11px;'>
                                © 2025 Horizia - Tous droits réservés
                            </p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
        ";
    }
    
}