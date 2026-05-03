<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DescriptionGeneratorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

class DescriptionGeneratorManagerTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private DescriptionGeneratorManager $manager;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manager = new DescriptionGeneratorManager($this->httpClient, $this->logger);
    }

    // ========== TESTS POUR generateDescription ==========

    public function testGenerateDescription(): void
    {
        $result = $this->manager->generateDescription('Paris', 'Ville', 'Paris', 'France');
        
        $this->assertIsString($result);
        $this->assertStringContainsString('Paris', $result);
        $this->assertStringContainsString('Ville', $result);
    }

    public function testGenerateDescriptionWithoutLocation(): void
    {
        $result = $this->manager->generateDescription('Voyage', 'Aventure', null, null);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Voyage', $result);
        $this->assertStringContainsString('Aventure', $result);
    }

    public function testGenerateDescriptionWithVilleOnly(): void
    {
        $result = $this->manager->generateDescription('Hammamet', 'Plage', 'Hammamet', null);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('Hammamet', $result);
        $this->assertStringContainsString('Plage', $result);
    }

    public function testGenerateDescriptionWithPaysOnly(): void
    {
        $result = $this->manager->generateDescription('Tunisie', 'Culture', null, 'Tunisie');
        
        $this->assertIsString($result);
        $this->assertStringContainsString('Tunisie', $result);
        $this->assertStringContainsString('Culture', $result);
    }

    public function testGenerateDescriptionWithEmptyValues(): void
    {
        $result = $this->manager->generateDescription('Test', 'Categorie', '', '');
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Test', $result);
        $this->assertStringContainsString('Categorie', $result);
    }

    // ========== TESTS POUR translateDescription ==========

    public function testTranslateDescription(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn(json_encode([
            'responseData' => ['translatedText' => 'Hello world']
        ]));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->translateDescription('Bonjour le monde', 'en');
        
        $this->assertEquals('Hello world', $result);
    }

    public function testTranslateDescriptionWithError(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->once())->method('error');

        $result = $this->manager->translateDescription('Bonjour', 'en');
        
        $this->assertEquals('Bonjour', $result);
    }

    public function testTranslateDescriptionWithDefaultLanguage(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn(json_encode([
            'responseData' => ['translatedText' => 'Hello']
        ]));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->translateDescription('Bonjour');
        
        $this->assertEquals('Hello', $result);
    }

    public function testTranslateDescriptionWithEmptyString(): void
    {
        $result = $this->manager->translateDescription('');
        
        $this->assertEquals('', $result);
    }

    public function testTranslateDescriptionWithMultipleLanguages(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn(json_encode([
            'responseData' => ['translatedText' => 'Hallo Welt']
        ]));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->translateDescription('Bonjour le monde', 'de');
        
        $this->assertEquals('Hallo Welt', $result);
    }

    public function testTranslateDescriptionWithApiReturningOriginalText(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn(json_encode([
            'responseData' => ['translatedText' => 'Bonjour le monde']
        ]));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->translateDescription('Bonjour le monde', 'en');
        
        $this->assertEquals('Bonjour le monde', $result);
    }
}