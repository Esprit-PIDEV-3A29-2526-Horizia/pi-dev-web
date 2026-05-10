<?php

namespace App\Tests\Service;

use App\Service\GeolocationService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;


#[CoversClass(GeolocationService::class)]
class GeolocationServiceTest extends TestCase
{
    // RÈGLE 1 : coordonnées GPS valides

    public function testCoordonneesValides(): void
    {
        $service = $this->creerService();

        $this->assertTrue($service->validerCoordonnees(36.8065, 10.1815));
    }

    public function testLatitudeHorsLimite(): void
    {
        $service = $this->creerService();

        $this->assertFalse($service->validerCoordonnees(91.0, 10.0));
    }

    public function testLongitudeHorsLimite(): void
    {
        $service = $this->creerService();

        $this->assertFalse($service->validerCoordonnees(36.0, 200.0));
    }

    public function testCoordonneesNegativesValides(): void
    {
        $service = $this->creerService();

        $this->assertTrue($service->validerCoordonnees(-33.87, 151.21));
    }

    // RÈGLE 2 : distance entre deux points correcte

    public function testDistanceMemePoint(): void
    {
        $service = $this->creerService();

        $this->assertEquals(0.0, $service->calculerDistance(36.8065, 10.1815, 36.8065, 10.1815));
    }

   public function testDistanceTunisVersSfax(): void
{
    $service = $this->creerService();

    $distance = $service->calculerDistance(36.8065, 10.1815, 34.7406, 10.7603);
    $this->assertGreaterThan(220, $distance);
    $this->assertLessThan(260, $distance);
}
    // RÈGLE 3 : formatage de distance lisible

   public function testFormaterDistanceEnKm(): void
{
    $service = $this->creerService();

    $this->assertEquals('5.0 km', $service->formaterDistance(5.0));
}

    public function testFormaterDistanceEnMetres(): void
    {
        $service = $this->creerService();

        $this->assertEquals('500 m', $service->formaterDistance(0.5));
    }

    // HELPER — mock des dépendances externes

    private function creerService(): GeolocationService
    {
        $httpClient = $this->createMock(\Symfony\Contracts\HttpClient\HttpClientInterface::class);
        $logger     = $this->createMock(\Psr\Log\LoggerInterface::class);

        return new GeolocationService($httpClient, $logger);
    }
}