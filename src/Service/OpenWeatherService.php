<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenWeatherService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $openWeatherApiKey)
    {
        $this->client = $client;
        $this->apiKey = $openWeatherApiKey;
    }

    /**
 * @return array{
 *     city: mixed,
 *     country: mixed,
 *     temperature: float|null,
 *     description: mixed,
 *     icon: mixed,
 *     humidity: mixed,
 *     wind: mixed,
 *     advice: string,
 *     badge: string
 * }|null
 */
public function getWeatherByCity(string $city): ?array
    {
        $city = trim($city);

        if ($city === '') {
            return null;
        }

        try {
            $response = $this->client->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();

            $temperature = isset($data['main']['temp']) ? (float) $data['main']['temp'] : null;
            $description = $data['weather'][0]['description'] ?? null;

            return [
                'city' => $data['name'] ?? $city,
                'country' => $data['sys']['country'] ?? null,
                'temperature' => $temperature,
                'description' => $description,
                'icon' => $data['weather'][0]['icon'] ?? null,
                'humidity' => $data['main']['humidity'] ?? null,
                'wind' => $data['wind']['speed'] ?? null,
                'advice' => $this->buildAdvice($temperature, $description),
                'badge' => $this->buildBadge($temperature, $description),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildAdvice(?float $temperature, ?string $description): string
    {
        $description = mb_strtolower((string) $description);

        if ($temperature === null) {
            return 'Météo indisponible pour le moment.';
        }

        if (str_contains($description, 'pluie') || str_contains($description, 'orage')) {
            return 'Temps instable : pensez à vérifier la période avant la réservation.';
        }

        if ($temperature >= 30) {
            return 'Temps très chaud : destination idéale pour un séjour plage et détente.';
        }

        if ($temperature >= 22) {
            return 'Temps agréable : très bonne période pour voyager.';
        }

        if ($temperature >= 15) {
            return 'Climat modéré : conditions correctes pour découvrir la destination.';
        }

        return 'Temps frais : prévoir des vêtements adaptés.';
    }

    private function buildBadge(?float $temperature, ?string $description): string
    {
        $description = mb_strtolower((string) $description);

        if ($temperature === null) {
            return 'Météo indisponible';
        }

        if (str_contains($description, 'pluie') || str_contains($description, 'orage')) {
            return 'Temps pluvieux';
        }

        if ($temperature >= 30) {
            return 'Idéal plage';
        }

        if ($temperature >= 22) {
            return 'Temps agréable';
        }

        if ($temperature >= 15) {
            return 'Climat modéré';
        }

        return 'Temps frais';
    }
}