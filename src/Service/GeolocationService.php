<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de géolocalisation utilisant OpenStreetMap et Nominatim
 * 100% Gratuit et illimité (respecter 1 requête/seconde)
 */
class GeolocationService
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org';
    private const USER_AGENT = 'Horizia-CarRental/1.0';
    private const DELAI_MIN_MS = 1000000; // 1 seconde en microsecondes

    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private ?float $dernierAppel = null;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Géocoder une adresse (Adresse → Coordonnées GPS)
     * @return array ['latitude' => string, 'longitude' => string, 'adresse_formatee' => string, 'ville' => string, 'code_postal' => string]
     */
   /** @return array<string, mixed> */
public function geocoderAdresse(string $adresse): array
    {
        $resultats = [];

        try {
            // Respecter le délai entre les requêtes
            $this->attendreDelaiMinimum();

            $this->logger->info('→ Géocodage de l\'adresse : ' . $adresse);

            // Encoder l'adresse pour l'URL
            $adresseEncodee = urlencode($adresse);

            // Construire l'URL de la requête
            $url = self::NOMINATIM_URL . "/search?q={$adresseEncodee}&format=json&addressdetails=1&limit=1&countrycodes=tn";

            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => self::USER_AGENT]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Erreur API : ' . $response->getStatusCode());
            }

            $data = $response->toArray();

            if (empty($data)) {
                $this->logger->warning('⚠ Aucun résultat trouvé pour cette adresse');
                $resultats['erreur'] = 'Adresse non trouvée';
                return $resultats;
            }

            $premier = $data[0];

            // Extraire les coordonnées
            $resultats['latitude'] = $premier['lat'];
            $resultats['longitude'] = $premier['lon'];
            $resultats['adresse_formatee'] = $premier['display_name'];

            // Extraire les détails de l'adresse
            if (isset($premier['address'])) {
                $address = $premier['address'];
                
                if (isset($address['city'])) {
                    $resultats['ville'] = $address['city'];
                } elseif (isset($address['town'])) {
                    $resultats['ville'] = $address['town'];
                } elseif (isset($address['village'])) {
                    $resultats['ville'] = $address['village'];
                }

                if (isset($address['postcode'])) {
                    $resultats['code_postal'] = $address['postcode'];
                }

                if (isset($address['road'])) {
                    $resultats['rue'] = $address['road'];
                }
            }

            $this->logger->info('✓ Géocodage réussi : ' . $resultats['latitude'] . ', ' . $resultats['longitude']);

        } catch (\Exception $e) {
            $this->logger->error('✗ Erreur géocodage : ' . $e->getMessage());
            $resultats['erreur'] = 'Erreur technique : ' . $e->getMessage();
        }

        return $resultats;
    }

    /**
     * Géocodage inverse (Coordonnées GPS → Adresse)
     */
   /** @return array<string, mixed> */
public function geocoderInverse(float $latitude, float $longitude): array
    {
        $resultats = [];

        try {
            $this->attendreDelaiMinimum();

            $this->logger->info('→ Géocodage inverse : ' . $latitude . ', ' . $longitude);

            $url = self::NOMINATIM_URL . "/reverse?lat={$latitude}&lon={$longitude}&format=json&addressdetails=1";

            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => self::USER_AGENT]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Erreur API : ' . $response->getStatusCode());
            }

            $data = $response->toArray();

            if (isset($data['error'])) {
                $this->logger->warning('⚠ Aucune adresse trouvée pour ces coordonnées');
                $resultats['erreur'] = 'Coordonnées invalides';
                return $resultats;
            }

            $resultats['adresse_formatee'] = $data['display_name'];

            if (isset($data['address'])) {
                $address = $data['address'];
                
                if (isset($address['city'])) {
                    $resultats['ville'] = $address['city'];
                } elseif (isset($address['town'])) {
                    $resultats['ville'] = $address['town'];
                }

                if (isset($address['postcode'])) {
                    $resultats['code_postal'] = $address['postcode'];
                }

                if (isset($address['road'])) {
                    $resultats['rue'] = $address['road'];
                }
            }

            $this->logger->info('✓ Géocodage inverse réussi');

        } catch (\Exception $e) {
            $this->logger->error('✗ Erreur géocodage inverse : ' . $e->getMessage());
            $resultats['erreur'] = 'Erreur technique : ' . $e->getMessage();
        }

        return $resultats;
    }

    /**
     * Calculer la distance entre deux points GPS (formule de Haversine)
     * @return float Distance en kilomètres
     */
    public function calculerDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $RAYON_TERRE_KM = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $RAYON_TERRE_KM * $c;
    }

    /**
     * Formater une distance en texte lisible
     */
    public function formaterDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000) . ' m';
        } else {
            return number_format($distanceKm, 1) . ' km';
        }
    }
/** @return array<string, float> */
public function getCoordonneesAgence(): array
{
    return [
        'latitude' => 36.90123398692758,
        'longitude' => 10.19090383100016
    ];
}
    /**
     * Calculer la distance entre un client et l'agence
     */
    public function calculerDistanceDepuisAgence(float $clientLat, float $clientLon): float
    {
        $agence = $this->getCoordonneesAgence();
        return $this->calculerDistance(
            $agence['latitude'],
            $agence['longitude'],
            $clientLat,
            $clientLon
        );
    }

    /**
     * Respecter le délai minimum entre les requêtes (1 seconde)
     */
    private function attendreDelaiMinimum(): void
    {
        if ($this->dernierAppel !== null) {
            $tempsEcoule = (microtime(true) - $this->dernierAppel) * 1000000;
            if ($tempsEcoule < self::DELAI_MIN_MS) {
                usleep((int)(self::DELAI_MIN_MS - $tempsEcoule));
            }
        }
        $this->dernierAppel = microtime(true);
    }

    /**
     * Valider des coordonnées GPS
     */
    public function validerCoordonnees(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90 &&
               $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Vérifier si une adresse est en Tunisie
     */
    public function estAdresseTunisienne(string $adresse): bool
    {
        if (empty($adresse)) return false;

        $adresseLower = strtolower($adresse);

        // Villes tunisiennes principales
        $villesTunisiennes = [
            'tunis', 'sfax', 'sousse', 'kairouan', 'bizerte', 'gabès', 'ariana',
            'gafsa', 'monastir', 'ben arous', 'kasserine', 'médenine', 'nabeul',
            'tataouine', 'béja', 'jendouba', 'mahdia', 'siliana', 'kébili',
            'zaghouan', 'manouba', 'tozeur', 'sidi bouzid'
        ];

        foreach ($villesTunisiennes as $ville) {
            if (str_contains($adresseLower, $ville)) {
                return true;
            }
        }

        return str_contains($adresseLower, 'tunisie') || str_contains($adresseLower, 'tunisia');
    }
}