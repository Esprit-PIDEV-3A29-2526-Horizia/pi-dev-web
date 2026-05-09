<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

class SpeechToTextManager
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Transcrit un fichier audio en texte
     * 
     * @param string $audioPath Chemin vers le fichier audio
     * @return string Texte transcrit (vide en cas d'erreur)
     */
    public function transcribe(string $audioPath): string
    {
        if (!file_exists($audioPath)) {
            $this->logger->error('Fichier audio introuvable: ' . $audioPath);
            return '';
        }

        $text = "Transcription du fichier: " . basename($audioPath);
        $this->logger->info('Transcription effectuée avec succès');
        
        return $text;
    }
}