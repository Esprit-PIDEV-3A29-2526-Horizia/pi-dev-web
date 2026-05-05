<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SpeechToTextManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SpeechToTextManagerTest extends TestCase
{
    private LoggerInterface $logger;
    private SpeechToTextManager $manager;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manager = new SpeechToTextManager($this->logger);
    }

    public function testTranscribeWithValidFile(): void
    {
        // Créer un fichier temporaire
        $tempFile = tempnam(sys_get_temp_dir(), 'audio_');
        file_put_contents($tempFile, 'contenu audio test');

        $result = $this->manager->transcribe($tempFile);

        $this->assertIsString($result);
        $this->assertStringContainsString('Transcription du fichier', $result);

        unlink($tempFile);
    }

    public function testTranscribeWithInvalidFile(): void
    {
        $this->logger->expects($this->once())->method('error');
        
        $result = $this->manager->transcribe('/chemin/inexistant.mp3');
        
        $this->assertEquals('', $result);
    }

    public function testTranscribeWithEmptyPath(): void
    {
        $this->logger->expects($this->once())->method('error');
        
        $result = $this->manager->transcribe('');
        
        $this->assertEquals('', $result);
    }
}