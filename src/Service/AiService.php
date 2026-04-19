<?php

namespace App\Service;

use App\Entity\Events;

class AiService
{
    private $apiKey;
    
    public function __construct()
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
    }
    
    private function callGemini(string $prompt): string
    {
        if (!$this->apiKey) {
            return "L'assistant IA n'est pas configuré.";
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
        
        return "🎉 Voici nos événements disponibles !";
    }
    
    public function getRecommendations(string $vibe, string $budget, string $when, array $events): array
    {
        if (empty($events)) {
            return [
                'text' => ' Pas encore d\'événements à venir ! Reviens bientôt ! ✨',
                'events' => []
            ];
        }
        
        // Filter events by budget
        $budgetInt = intval($budget);
        $filteredEvents = array_filter($events, function($event) use ($budgetInt) {
            return floatval($event->getPrix()) <= $budgetInt;
        });
        
        if (empty($filteredEvents)) {
            $filteredEvents = $events;
        }
        
        $recommendedEvents = array_slice($filteredEvents, 0, 3);
        
        // Short, simple message without listing all event details
        $text = " J'ai trouvé ces événements pour toi !\n\n";
        $text .= "✨ Selon tes critères (ambiance: $vibe, budget: $budget TND, quand: $when).\n\n";
        $text .= "Clique sur un événement ci-dessous pour voir les détails et t'inscrire ! 🎫";
        
        return [
            'text' => $text,
            'events' => $recommendedEvents
        ];
    }
    
    public function chat(string $userMessage, array $events): array
    {
        if (empty($events)) {
            return [
                'text' => '🎉 Pas encore d\'événements programmés ! Reviens bientôt ! ✨',
                'events' => []
            ];
        }
        
        $messageLower = strtolower($userMessage);
        $filteredEvents = [];
        $responseText = "";
        
        // Check for price filter
        if (preg_match('/(\d+)\s*tnd|moins de\s*(\d+)|under\s*(\d+)/i', $userMessage, $matches)) {
            $priceLimit = 0;
            foreach ($matches as $match) {
                if (is_numeric($match) && $match > 0) {
                    $priceLimit = intval($match);
                    break;
                }
            }
            
            if ($priceLimit > 0) {
                $filteredEvents = array_filter($events, function($event) use ($priceLimit) {
                    return floatval($event->getPrix()) <= $priceLimit;
                });
                
                if (!empty($filteredEvents)) {
                    $filteredEvents = array_slice($filteredEvents, 0, 5);
                    $responseText = "🎉 Voici les événements à moins de {$priceLimit} TND :\n\n";
                    $responseText .= "Clique sur un événement ci-dessous pour voir les détails ! 🎫";
                    return [
                        'text' => $responseText,
                        'events' => $filteredEvents
                    ];
                } else {
                    return [
                        'text' => "🎉 Désolé, il n'y a pas d'événements à moins de {$priceLimit} TND pour le moment. Regarde notre sélection ci-dessus ! ✨",
                        'events' => []
                    ];
                }
            }
        }
        
        // Check for category filter
        $categories = ['concert', 'festival', 'conference', 'sport', 'theatre', 'atelier'];
        foreach ($categories as $cat) {
            if (strpos($messageLower, $cat) !== false) {
                $filteredEvents = array_filter($events, function($event) use ($cat) {
                    return stripos($event->getCategorie(), $cat) !== false;
                });
                
                if (!empty($filteredEvents)) {
                    $filteredEvents = array_slice($filteredEvents, 0, 5);
                    $responseText = "🎉 Voici les {$cat}s à venir :\n\n";
                    $responseText .= "Clique sur un événement ci-dessous pour découvrir les détails ! 🎫";
                    return [
                        'text' => $responseText,
                        'events' => $filteredEvents
                    ];
                }
            }
        }
        
        // Default response with some random events
        $randomEvents = array_slice($events, 0, 3);
        return [
            'text' => "🎉 Découvre notre sélection d'événements ci-dessous ! Tu peux aussi me dire ton budget (ex: \"moins de 50 TND\") ou le type d'événement que tu recherches (concert, festival, sport...). 💬",
            'events' => $randomEvents
        ];
    }
}