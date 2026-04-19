<?php

namespace App\Service;

use App\Entity\Location;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service WhatsApp via Twilio Sandbox
 * Envoie des notifications WhatsApp aux clients lors des événements de location
 */
class WhatsAppService
{
    private const TWILIO_API_URL  = 'https://api.twilio.com/2010-04-01/Accounts/';
    private const SANDBOX_NUMBER  = 'whatsapp:+14155238886'; // Numéro sandbox Twilio fixe
    private const NOM_AGENCE      = 'Horizia';
    private const TEL_AGENCE      = '+216 53 661 445';

    private HttpClientInterface $httpClient;
    private LoggerInterface     $logger;
    private string              $accountSid;
    private string              $authToken;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $accountSid,
        string $authToken
    ) {
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
        $this->accountSid = $accountSid;
        $this->authToken  = $authToken;
    }

    // ══════════════════════════════════════════════════════════
    // 📱 Confirmation de réservation
    // ══════════════════════════════════════════════════════════
    public function envoyerConfirmationReservation(Location $location, string $telephone): array
    {
        $debut    = $location->getDateDebut()?->format('d/m/Y') ?? '—';
        $fin      = $location->getDateFinPrevue()?->format('d/m/Y') ?? '—';
        $immat    = $location->getVehicule()?->getImmatriculation() ?? '—';
        $montant  = number_format((float) $location->getMontantTotal(), 3);
        $nom      = $location->getClientNomComplet() ?? 'Client';
        $id       = sprintf('%04d', $location->getIdLocation());

        $message = "🚗 *HORIZIA — Confirmation de location*\n\n"
            . "Bonjour *{$nom}*,\n"
            . "Votre réservation est confirmée ✅\n\n"
            . "📋 *Détails :*\n"
            . "• N° : HOZ-{$id}\n"
            . "• Véhicule : {$immat}\n"
            . "• Du : {$debut}\n"
            . "• Au : {$fin}\n"
            . "• Montant total : {$montant} TND\n\n"
            . "📍 *Horizia* — " . self::TEL_AGENCE . "\n"
            . "Merci de votre confiance ! 🙏";

        return $this->envoyer($telephone, $message);
    }

    // ══════════════════════════════════════════════════════════
    // ⏰ Rappel 24h avant la prise en charge
    // ══════════════════════════════════════════════════════════
    public function envoyerRappel(Location $location, string $telephone): array
    {
        $debut = $location->getDateDebut()?->format('d/m/Y à H:i') ?? '—';
        $immat = $location->getVehicule()?->getImmatriculation() ?? '—';
        $nom   = $location->getClientNomComplet() ?? 'Client';

        $message = "⏰ *HORIZIA — Rappel de location*\n\n"
            . "Bonjour *{$nom}*,\n"
            . "Votre location commence demain !\n\n"
            . "🚗 Véhicule : *{$immat}*\n"
            . "📅 Date : *{$debut}*\n\n"
            . "N'oubliez pas votre *CIN* et *permis de conduire*.\n\n"
            . "📞 Horizia : " . self::TEL_AGENCE;

        return $this->envoyer($telephone, $message);
    }

    // ══════════════════════════════════════════════════════════
    // 🧾 Notification de retour / fin de location
    // ══════════════════════════════════════════════════════════
    public function envoyerNotificationRetour(Location $location, string $telephone): array
    {
        $fin     = $location->getDateFinPrevue()?->format('d/m/Y') ?? '—';
        $immat   = $location->getVehicule()?->getImmatriculation() ?? '—';
        $nom     = $location->getClientNomComplet() ?? 'Client';
        $solde   = number_format($location->getResteAPayer(), 3);

        $message = "🏁 *HORIZIA — Fin de location*\n\n"
            . "Bonjour *{$nom}*,\n"
            . "Votre location se termine le *{$fin}*.\n\n"
            . "🚗 Véhicule : *{$immat}*\n"
            . "💰 Reste à payer : *{$solde} TND*\n\n"
            . "Merci de restituer le véhicule à l'agence.\n"
            . "📞 Horizia : " . self::TEL_AGENCE;

        return $this->envoyer($telephone, $message);
    }

    // ══════════════════════════════════════════════════════════
    // ❌ Notification d'annulation
    // ══════════════════════════════════════════════════════════
    public function envoyerAnnulation(Location $location, string $telephone): array
    {
        $nom   = $location->getClientNomComplet() ?? 'Client';
        $immat = $location->getVehicule()?->getImmatriculation() ?? '—';
        $id    = sprintf('%04d', $location->getIdLocation());

        $message = "❌ *HORIZIA — Annulation de location*\n\n"
            . "Bonjour *{$nom}*,\n"
            . "Votre location HOZ-{$id} ({$immat}) a été annulée.\n\n"
            . "Pour toute question :\n"
            . "📞 " . self::TEL_AGENCE . "\n\n"
            . "Nous espérons vous revoir bientôt ! 🙏";

        return $this->envoyer($telephone, $message);
    }

    // ══════════════════════════════════════════════════════════
    // 📤 Méthode centrale d'envoi via Twilio REST API
    // ══════════════════════════════════════════════════════════
    public function envoyer(string $telephoneDestinataire, string $message): array
    {
        // Normaliser le numéro de téléphone
        $telephone = $this->normaliserTelephone($telephoneDestinataire);
        if (!$telephone) {
            return [
                'succes'  => false,
                'message' => 'Numéro de téléphone invalide : ' . $telephoneDestinataire,
            ];
        }

        $url = self::TWILIO_API_URL . $this->accountSid . '/Messages.json';

        try {
            $response = $this->httpClient->request('POST', $url, [
                'auth_basic'        => [$this->accountSid, $this->authToken],
                'body'              => [
                    'From' => self::SANDBOX_NUMBER,
                    'To'   => 'whatsapp:' . $telephone,
                    'Body' => $message,
                ],
                'timeout'           => 10,
                'verify_peer'       => false,
                'verify_host'       => false,
            ]);

            $statusCode = $response->getStatusCode();
            $data       = $response->toArray(false);

            if (in_array($statusCode, [200, 201])) {
                $this->logger->info('WhatsApp envoyé à ' . $telephone . ' — SID: ' . ($data['sid'] ?? '?'));
                return [
                    'succes'  => true,
                    'message' => 'WhatsApp envoyé à ' . $telephone,
                    'sid'     => $data['sid'] ?? null,
                ];
            }

            $erreur = $data['message'] ?? ('HTTP ' . $statusCode);
            $this->logger->error('Twilio WhatsApp erreur — ' . $erreur);
            return [
                'succes'  => false,
                'message' => 'Erreur Twilio : ' . $erreur,
                'code'    => $data['code'] ?? $statusCode,
            ];

        } catch (\Exception $e) {
            $this->logger->error('WhatsAppService::envoyer — ' . $e->getMessage());
            return [
                'succes'  => false,
                'message' => 'Erreur réseau : ' . $e->getMessage(),
            ];
        }
    }

    // ══════════════════════════════════════════════════════════
    // 🔧 Normaliser le numéro au format E.164 (+216XXXXXXXX)
    // ══════════════════════════════════════════════════════════
    private function normaliserTelephone(string $telephone): ?string
    {
        // Supprimer tout sauf les chiffres et le +
        $tel = preg_replace('/[^\d+]/', '', $telephone);

        // Déjà au format international
        if (str_starts_with($tel, '+')) {
            return strlen($tel) >= 10 ? $tel : null;
        }

        // Numéro tunisien sans indicatif (8 chiffres → +216XXXXXXXX)
        if (preg_match('/^[2-9]\d{7}$/', $tel)) {
            return '+216' . $tel;
        }

        // Numéro avec indicatif tunisien sans +
        if (str_starts_with($tel, '216') && strlen($tel) === 11) {
            return '+' . $tel;
        }

        // Numéro commençant par 00
        if (str_starts_with($tel, '00')) {
            return '+' . substr($tel, 2);
        }

        return null;
    }
}