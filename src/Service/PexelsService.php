<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PexelsService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $pexelsApiKey,
        private LoggerInterface $logger
    ) {
    }

    public function searchImage(string $query): ?string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.pexels.com/v1/search', [
                'headers' => [
                    'Authorization' => $this->pexelsApiKey,
                ],
                'query' => [
                    'query' => $query,
                    'per_page' => 1,
                    'orientation' => 'landscape',
                ],
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            $this->logger->info('PEXELS RESPONSE', [
                'query' => $query,
                'status' => $statusCode,
                'photos_count' => count($data['photos'] ?? []),
            ]);

            if ($statusCode >= 400 || empty($data['photos'])) {
                return null;
            }

            $src = $data['photos'][0]['src'] ?? [];

            return $src['large2x']
                ?? $src['large']
                ?? $src['medium']
                ?? $src['original']
                ?? null;
        } catch (\Throwable $e) {
            $this->logger->error('Pexels searchImage exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }
}