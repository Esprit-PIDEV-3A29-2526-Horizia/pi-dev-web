<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private const API_URL     = 'https://api.openweathermap.org/data/2.5';
    private const CITY        = 'Tunis';
    private const COUNTRY     = 'TN';
    private const LANG        = 'fr';
    private const UNITS       = 'metric';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey
    ) {}

    /**
     * Météo actuelle à Tunis
     * @return array<string, mixed>|null
     */
    public function getMeteoActuelle(): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/weather', [
                'query' => [
                    'q'     => self::CITY . ',' . self::COUNTRY,
                    'appid' => $this->apiKey,
                    'lang'  => self::LANG,
                    'units' => self::UNITS,
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            return [
                'temperature'   => (int) round($data['main']['temp']),
                'ressenti'      => (int) round($data['main']['feels_like']),
                'humidity'      => $data['main']['humidity'],
                'description'   => ucfirst($data['weather'][0]['description']),
                'icone'         => $data['weather'][0]['icon'],
                'icone_url'     => 'https://openweathermap.org/img/wn/' . $data['weather'][0]['icon'] . '@2x.png',
                'vent'          => round($data['wind']['speed'] * 3.6, 1),
                'ville'         => $data['name'],
                'code'          => $data['weather'][0]['id'],
                'alerte'        => $this->detecterAlerte($data['weather'][0]['id']),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prévisions sur 5 jours
     * @return array<int, array<string, mixed>>
     */
    public function getPrevisions5Jours(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/forecast', [
                'query' => [
                    'q'     => self::CITY . ',' . self::COUNTRY,
                    'appid' => $this->apiKey,
                    'lang'  => self::LANG,
                    'units' => self::UNITS,
                    'cnt'   => 40,
                ],
                'timeout' => 5,
            ]);

            $data  = $response->toArray();
            $jours = [];
            $vus   = [];

            foreach ($data['list'] as $item) {
                $date  = date('Y-m-d', $item['dt']);
                $heure = (int) date('H', $item['dt']);

                if (in_array($date, $vus) && $heure !== 12) {
                    continue;
                }

                if (!in_array($date, $vus)) {
                    $vus[] = $date;
                }

                $jours[$date] = [
                    'date'        => $date,
                    'jour_nom'    => $this->nomJour($item['dt']),
                    'temperature' => (int) round($item['main']['temp']),
                    'temp_min'    => (int) round($item['main']['temp_min']),
                    'temp_max'    => (int) round($item['main']['temp_max']),
                    'description' => ucfirst($item['weather'][0]['description']),
                    'icone_url'   => 'https://openweathermap.org/img/wn/' . $item['weather'][0]['icon'] . '@2x.png',
                    'code'        => $item['weather'][0]['id'],
                    'pluie'       => isset($item['rain']['3h']) ? round($item['rain']['3h'], 1) : 0,
                    'alerte'      => $this->detecterAlerte($item['weather'][0]['id']),
                ];

                if (count($jours) >= 5) break;
            }

            return array_values($jours);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Détecte une alerte météo
     * @return array<string, string>|null
     */
    private function detecterAlerte(int $code): ?array
    {
        if ($code >= 200 && $code < 300) {
            return ['type' => 'danger', 'message' => '⛈️ Orage prévu — informer les clients'];
        }
        if ($code >= 500 && $code < 600) {
            return ['type' => 'warning', 'message' => '🌧️ Pluie prévue — vérifier les retours'];
        }
        if ($code >= 600 && $code < 700) {
            return ['type' => 'info', 'message' => '❄️ Conditions hivernales'];
        }
        if ($code >= 700 && $code < 800) {
            return ['type' => 'warning', 'message' => '🌫️ Visibilité réduite — conduire prudemment'];
        }
        return null;
    }

    private function nomJour(int $timestamp): string
    {
        $jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        return $jours[(int) date('w', $timestamp)];
    }
}