<?php

namespace App\Tests\Service;

use App\Service\AiContentGeneratorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AiContentGeneratorManagerTest extends TestCase
{
    private $httpClient;
    private $manager;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->manager = new AiContentGeneratorManager($this->httpClient);
    }

    public function testGenerateAllReturnsArray(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('{"titre":"Test","categorie":"Plage","description":"Description","tags":"tag1,tag2","imagePrompt":"prompt"}');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->generateAll('voyage');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('titre', $result);
        $this->assertArrayHasKey('categorie', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertArrayHasKey('imagePrompt', $result);
    }

    public function testGenerateAllWithEmptySubject(): void
    {
        $result = $this->manager->generateAll('');
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('Ville', $result['categorie']);
    }

    public function testGenerateAllWithApiError(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $result = $this->manager->generateAll('voyage');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('titre', $result);
        $this->assertArrayHasKey('description', $result);
    }

    public function testGenerateAllReturnsDefaultValues(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('invalid json');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->generateAll('Paris');

        $this->assertIsArray($result);
        $this->assertEquals('Paris', $result['titre']);
    }
}