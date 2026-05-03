<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\AiImageGeneratorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AiImageGeneratorManagerTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private AiImageGeneratorManager $manager;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->manager = new AiImageGeneratorManager($this->httpClient);
    }

    public function testGenerateImageReturnsString(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('contenu_image');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->generateImage('test prompt');

        $this->assertIsString($result);
    }

    public function testGenerateImageWithEmptyPrompt(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('contenu_image');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->generateImage('');
        
        $this->assertIsString($result);
    }

    public function testGenerateImageWithApiError(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $result = $this->manager->generateImage('test');

        $this->assertNull($result);
    }

    public function testGenerateImageWithLongPrompt(): void
    {
        $longPrompt = str_repeat('a', 1000);
        
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('contenu_image');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->generateImage($longPrompt);

        $this->assertIsString($result);
    }

    public function testGenerateImageWithSpecialCharacters(): void
    {
        $specialPrompt = 'Paysage avec des caractères spéciaux : é à ç û î ô';
        
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('contenu_image');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->generateImage($specialPrompt);

        $this->assertIsString($result);
    }

    public function testGenerateImageReturnsUrlFormat(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn('contenu_image');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->manager->generateImage('test');

        $this->assertStringStartsWith('/uploads/ai_images/', $result);
    }
}