<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CompreFaceService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $baseUrl
    ) {}

    public function verifyFaces(string $image1Base64, string $image2Base64): bool
{
    $temp1 = tempnam(sys_get_temp_dir(), 'face1_');
    file_put_contents($temp1, base64_decode($image1Base64));
    $temp2 = tempnam(sys_get_temp_dir(), 'face2_');
    file_put_contents($temp2, base64_decode($image2Base64));

    $response = $this->httpClient->request('POST', $this->baseUrl . '/api/v1/verification/verify', [
        'headers' => ['x-api-key' => $this->apiKey],
        'body' => [
            'file1' => fopen($temp1, 'r'),
            'file2' => fopen($temp2, 'r'),
        ],
    ]);

    unlink($temp1);
    unlink($temp2);

    $data = $response->toArray();
    return $data['result'][0]['verified'] ?? false;
}
}