<?php

namespace App\Service;

use App\Entity\Location;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WhatsAppService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private string $twilioApiUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $accountSid,
        string $authToken,
        string $whatsappFromNumber
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->fromNumber = $whatsappFromNumber;
        $this->twilioApiUrl = 'https://api.twilio.com/2010-04-01/Accounts/' . $accountSid . '/Messages.json';
        
        // Log la configuration (masquer les tokens)
        $this->logger->info('WhatsAppService initialisé', [
            'account_sid' => substr($accountSid, 0, 5) . '...',
            'from_number' => $whatsappFromNumber,
            'has_auth_token' => !empty($authToken)
        ]);
    }

    public function envoyerConfirmationReservation(Location $location, string $telephone): array
    {
        $debut = $location->getDateDebut()?->format('d/m/Y') ?? '—';
        $fin = $location->getDateFinPrevue()?->format('d/m/Y') ?? '—';
        $vehicule = $location->getVehicule();
        $immat = $vehicule?->getImmatriculation() ?? '—';
        
        $marque = '';
        $modele = '';
        if ($vehicule && $vehicule->getModele()) {
            $marque = $vehicule->getModele()->getMarque()?->getNomMarque() ?? '';
            $modele = $vehicule->getModele()->getNomModele() ?? '';
        }
        
        $montant = number_format((float) $location->getMontantTotal(), 3, '.', '');
        $nom = $location->getClientNomComplet() ?? 'Client';
        $id = sprintf('%04d', $location->getIdLocation());

        $message = "🚗 *HORIZIA — Confirmation de location*\n\n"
            . "Bonjour *{$nom}*,\n"
            . "Votre réservation est confirmée ✅\n\n"
            . "📋 *Détails :*\n"
            . "• N° : HOZ-{$id}\n"
            . "• Véhicule : {$marque} {$modele} ({$immat})\n"
            . "• Du : {$debut}\n"
            . "• Au : {$fin}\n"
            . "• Montant total : {$montant} TND\n\n"
            . "📍 *Horizia* — +216 53 661 445\n"
            . "Merci de votre confiance ! 🙏";

        return $this->envoyer($telephone, $message);
    }

    public function envoyer(string $telephoneDestinataire, string $message): array
    {
        // Normaliser le numéro
        $telephone = $this->normaliserTelephone($telephoneDestinataire);
        
        if (!$telephone) {
            $this->logger->error('WhatsApp: numéro invalide', ['original' => $telephoneDestinataire]);
            return [
                'succes' => false,
                'message' => 'Numéro invalide: ' . $telephoneDestinataire
            ];
        }

        // Vérifier les credentials
        if (empty($this->accountSid) || empty($this->authToken)) {
            return [
                'succes' => false,
                'message' => 'Twilio non configuré (SID ou Token manquant)'
            ];
        }

        if (empty($this->fromNumber)) {
            return [
                'succes' => false,
                'message' => 'Numéro WhatsApp Twilio non configuré'
            ];
        }

        try {
            $this->logger->info('WhatsApp: tentative envoi', [
                'to' => $telephone,
                'from' => $this->fromNumber,
                'message_length' => strlen($message)
            ]);

            $response = $this->httpClient->request('POST', $this->twilioApiUrl, [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'body' => [
                    'From' => $this->fromNumber,
                    'To' => 'whatsapp:' . $telephone,
                    'Body' => $message,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            $this->logger->info('WhatsApp: réponse Twilio', [
                'status_code' => $statusCode,
                'response' => $data
            ]);

            if ($statusCode === 200 || $statusCode === 201) {
                return [
                    'succes' => true,
                    'message' => 'Message envoyé à ' . $telephone,
                    'sid' => $data['sid'] ?? null
                ];
            }

            // Erreur Twilio
            $errorMessage = $data['message'] ?? 'Erreur inconnue';
            $errorCode = $data['code'] ?? $statusCode;
            
            return [
                'succes' => false,
                'message' => "Twilio erreur ($errorCode): $errorMessage"
            ];

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp: exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'succes' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    private function normaliserTelephone(string $telephone): ?string
    {
        // Nettoyer le numéro
        $tel = preg_replace('/[^0-9+]/', '', trim($telephone));
        
        if (empty($tel)) {
            return null;
        }

        // Déjà au format international
        if (str_starts_with($tel, '+')) {
            return $tel;
        }

        // Numéro avec indicatif 216 sans +
        if (str_starts_with($tel, '216') && strlen($tel) === 11) {
            return '+' . $tel;
        }

        // Numéro tunisien à 8 chiffres
        if (preg_match('/^[259]\d{7}$/', $tel)) {
            return '+216' . $tel;
        }

        // Numéro avec 0 au début
        if (preg_match('/^0[259]\d{7}$/', $tel)) {
            return '+216' . substr($tel, 1);
        }

        $this->logger->warning('WhatsApp: format non reconnu', ['original' => $telephone, 'cleaned' => $tel]);
        return null;
    }

    public function testerConnexion(): array
    {
        if (empty($this->accountSid) || empty($this->authToken)) {
            return ['success' => false, 'message' => 'Credentials Twilio manquants'];
        }
        
        try {
            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $this->accountSid . '.json';
            $response = $this->httpClient->request('GET', $url, [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'timeout' => 10,
            ]);
            
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return [
                    'success' => true, 
                    'message' => 'Connexion Twilio OK',
                    'account_name' => $data['friendly_name'] ?? 'N/A'
                ];
            }
            return ['success' => false, 'message' => 'Erreur HTTP: ' . $response->getStatusCode()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}