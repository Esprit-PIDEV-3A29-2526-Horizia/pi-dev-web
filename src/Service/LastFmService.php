<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LastFmService
{
    private HttpClientInterface $httpClient;
    private string $apiKey = '480b3708acd6cf54465837aa89bc8310';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

   /** @return array<string, mixed>|null */
public function getArtistInfo(string $artistName): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'http://ws.audioscrobbler.com/2.0/', [
                'query' => [
                    'method' => 'artist.getinfo',
                    'artist' => $artistName,
                    'api_key' => $this->apiKey,
                    'format' => 'json',
                    'lang' => 'fr', // French language
                ],
            ]);

            $data = $response->toArray();
            
            if (isset($data['artist'])) {
                // Get top tracks
                $topTracks = $this->getTopTracks($artistName);
                
                return [
                    'name' => $data['artist']['name'],
                    'bio' => $data['artist']['bio']['summary'] ?? 'Aucune biographie disponible',
                    'image' => $data['artist']['image'][3]['#text'] ?? null, // Large image
                    'image_small' => $data['artist']['image'][1]['#text'] ?? null, // Medium image
                    'listeners' => number_format($data['artist']['stats']['listeners'] ?? 0),
                    'playcount' => number_format($data['artist']['stats']['playcount'] ?? 0),
                    'tags' => $data['artist']['tags']['tag'] ?? [],
                    'top_tracks' => $topTracks,
                ];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

   /** @return array<int, array<string, mixed>> */
public function getTopTracks(string $artistName, int $limit = 5): array
    {
        try {
            $response = $this->httpClient->request('GET', 'http://ws.audioscrobbler.com/2.0/', [
                'query' => [
                    'method' => 'artist.gettoptracks',
                    'artist' => $artistName,
                    'api_key' => $this->apiKey,
                    'format' => 'json',
                    'limit' => $limit,
                    'lang' => 'fr',
                ],
            ]);

            $data = $response->toArray();
            
            $tracks = [];
            if (isset($data['toptracks']['track'])) {
                foreach ($data['toptracks']['track'] as $track) {
                    $tracks[] = [
                        'name' => $track['name'],
                        'playcount' => number_format($track['playcount'] ?? 0),
                        'listeners' => number_format($track['listeners'] ?? 0),
                        'url' => $track['url'] ?? null,
                        'image' => $track['image'][2]['#text'] ?? null,
                    ];
                }
            }
            return $tracks;
        } catch (\Exception $e) {
            return [];
        }
    }

   /** @return array<int, array<string, mixed>> */
public function getSimilarArtists(string $artistName, int $limit = 5): array
    {
        try {
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
            
            $artists = [];
            if (isset($data['similarartists']['artist'])) {
                foreach ($data['similarartists']['artist'] as $artist) {
                    $artists[] = [
                        'name' => $artist['name'],
                        'url' => $artist['url'] ?? null,
                        'image' => $artist['image'][2]['#text'] ?? null,
                    ];
                }
            }
            return $artists;
        } catch (\Exception $e) {
            return [];
        }
    }
}