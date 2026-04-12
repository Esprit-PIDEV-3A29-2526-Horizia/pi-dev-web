<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LastFmService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * Get artist information (bio, image, listeners, playcount)
     */
    public function getArtistInfo(string $artistName): ?array
    {
        $response = $this->httpClient->request('GET', 'http://ws.audioscrobbler.com/2.0/', [
            'query' => [
                'method' => 'artist.getinfo',
                'artist' => $artistName,
                'api_key' => $this->apiKey,
                'format' => 'json',
            ],
        ]);

        $data = $response->toArray();
        
        if (isset($data['artist'])) {
            return [
                'name' => $data['artist']['name'],
                'bio' => $data['artist']['bio']['summary'] ?? 'No bio available',
                'image' => $data['artist']['image'][3]['#text'] ?? null, // Large image
                'listeners' => $data['artist']['stats']['listeners'] ?? '0',
                'playcount' => $data['artist']['stats']['playcount'] ?? '0',
                'tags' => $data['artist']['tags']['tag'] ?? [],
                'similar' => $data['artist']['similar']['artist'] ?? [],
            ];
        }

        return null;
    }

    /**
     * Get artist's top tracks
     */
    public function getTopTracks(string $artistName, int $limit = 5): array
    {
        $response = $this->httpClient->request('GET', 'http://ws.audioscrobbler.com/2.0/', [
            'query' => [
                'method' => 'artist.gettoptracks',
                'artist' => $artistName,
                'api_key' => $this->apiKey,
                'format' => 'json',
                'limit' => $limit,
            ],
        ]);

        $data = $response->toArray();
        
        if (isset($data['toptracks']['track'])) {
            return $data['toptracks']['track'];
        }

        return [];
    }

    /**
     * Get similar artists
     */
    public function getSimilarArtists(string $artistName, int $limit = 5): array
    {
        $response = $this->httpClient->request('GET', 'http://ws.audioscrobbler.com/2.0/', [
            'query' => [
                'method' => 'artist.getsimilar',
                'artist' => $artistName,
                'api_key' => $this->apiKey,
                'format' => 'json',
                'limit' => $limit,
            ],
        ]);

        $data = $response->toArray();
        
        if (isset($data['similarartists']['artist'])) {
            return $data['similarartists']['artist'];
        }

        return [];
    }

    /**
     * Search for an artist
     */
    public function searchArtist(string $artistName): array
    {
        $response = $this->httpClient->request('GET', 'http://ws.audioscrobbler.com/2.0/', [
            'query' => [
                'method' => 'artist.search',
                'artist' => $artistName,
                'api_key' => $this->apiKey,
                'format' => 'json',
            ],
        ]);

        $data = $response->toArray();
        
        if (isset($data['results']['artistmatches']['artist'])) {
            return $data['results']['artistmatches']['artist'];
        }

        return [];
    }
}