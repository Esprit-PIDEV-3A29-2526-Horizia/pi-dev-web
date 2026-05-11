<?php

namespace App\Tests\Service;

use App\Entity\Location;
use App\Entity\Marque;
use App\Entity\Modele;
use App\Entity\User;
use App\Entity\Vehicule;
use App\Service\LocationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LocationManagerTest extends TestCase
{
    private $entityManager;
    private $repository;
    private $queryBuilder;
    private $query;
    private LocationManager $locationManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);
        
        $this->locationManager = new LocationManager($this->entityManager);
    }

    private function createValidVehicule(): Vehicule
    {
        $marque = new Marque();
        $marque->setNomMarque('Toyota');
        
        $modele = new Modele();
        $modele->setNomModele('Corolla');
        $modele->setMarque($marque);
        
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('1234TUN');
        $vehicule->setModele($modele);
        $vehicule->setPrixParJour('100.000');
        
        return $vehicule;
    }

    private function createValidUser(): User
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setTelephone('12345678');
        
        return $user;
    }

    private function createValidLocation(): Location
    {
        $vehicule = $this->createValidVehicule();
        $user = $this->createValidUser();
        
        $location = new Location();
        $location->setVehicule($vehicule);
        $location->setUser($user);
        $location->fillFromUser($user);
        $location->setDateDebut(new \DateTime('+5 days'));
        $location->setDateFinPrevue(new \DateTime('+10 days'));
        $location->setPrixParJour('100.000');
        $location->setStatut('réservée');
        $location->calculerMontantTotal();
        
        return $location;
    }

    // ========== TESTS CALCULS ==========

    public function testCalculerNbJours(): void
    {
        $debut = new \DateTime('2025-06-01');
        $fin = new \DateTime('2025-06-05');
        
        $nbJours = $this->locationManager->calculerNbJours($debut, $fin);
        
        $this->assertEquals(4, $nbJours);
    }

    public function testCalculerNbJoursMinimum1(): void
    {
        $debut = new \DateTime('2025-06-01');
        $fin = new \DateTime('2025-06-01');
        
        $nbJours = $this->locationManager->calculerNbJours($debut, $fin);
        
        $this->assertEquals(1, $nbJours);
    }

    public function testCalculerMontantTotal(): void
    {
        $vehicule = $this->createValidVehicule();
        $vehicule->setPrixParJour('100.000');
        
        $montantTotal = $this->locationManager->calculerMontantTotal($vehicule, 5);
        
        $this->assertEquals(500.0, $montantTotal);
    }

    public function testCalculerMontantTotalPrixZero(): void
    {
        $vehicule = $this->createValidVehicule();
        $vehicule->setPrixParJour('0.000');
        
        $montantTotal = $this->locationManager->calculerMontantTotal($vehicule, 5);
        
        $this->assertEquals(0.0, $montantTotal);
    }

    public function testCalculerAvance(): void
    {
        $avance = $this->locationManager->calculerAvance(1000.0);
        
        $this->assertEquals(300.0, $avance);
    }

    public function testCalculerResteAPayer(): void
    {
        $reste = $this->locationManager->calculerResteAPayer(1000.0, 300.0);
        
        $this->assertEquals(700.0, $reste);
    }

    public function testCalculerResteAPayerNonNegatif(): void
    {
        $reste = $this->locationManager->calculerResteAPayer(100.0, 200.0);
        
        $this->assertEquals(0.0, $reste);
    }

    // ========== TESTS VALIDATION ==========

    public function testValidLocation(): void
    {
        $location = $this->createValidLocation();
        
        // Mock pour isVehiculeDisponible
        $this->queryBuilder->method('where')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('andWhere')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('setParameter')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('getResult')->willReturn([]);
        $this->repository->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getRepository')->willReturn($this->repository);
        
        $result = $this->locationManager->validate($location);
        $this->assertTrue($result);
    }

    public function testLocationDateDebutPassee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de début ne peut pas être dans le passé.');
        
        $location = $this->createValidLocation();
        $location->setDateDebut(new \DateTime('-1 day'));
        
        $this->locationManager->validate($location);
    }

    public function testLocationDateFinAvantDateDebut(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');
        
        $location = $this->createValidLocation();
        $location->setDateFinPrevue(new \DateTime('+3 days')); // Avant date début (+5)
        
        $this->locationManager->validate($location);
    }

    public function testLocationDateFinEgaleDateDebut(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');
        
        $date = new \DateTime('+5 days');
        $location = $this->createValidLocation();
        $location->setDateDebut($date);
        $location->setDateFinPrevue($date);
        
        $this->locationManager->validate($location);
    }

    // ========== TESTS ANNULATION ==========

    public function testAnnulerLocationValide(): void
    {
        $location = $this->createValidLocation();
        $location->setDateDebut(new \DateTime('+10 days')); // Plus de 7 jours
        $location->setStatut('réservée');
        
        $this->entityManager->expects($this->once())->method('flush');
        
        $result = $this->locationManager->annuler($location);
        
        $this->assertEquals('annulee', $result->getStatut());
    }

    public function testAnnulerLocationTropTard(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Annulation impossible : moins de 7 jours avant le début.');
        
        $location = $this->createValidLocation();
        $location->setDateDebut(new \DateTime('+3 days')); // Moins de 7 jours
        
        $this->locationManager->annuler($location);
    }

    // ========== TESTS SAVE ==========

    public function testSaveLocationAutoCalcul(): void
    {
        $location = $this->createValidLocation();
        
        $this->queryBuilder->method('where')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('andWhere')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('setParameter')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('getResult')->willReturn([]);
        $this->repository->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getRepository')->willReturn($this->repository);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        
        $savedLocation = $this->locationManager->save($location);
        
        // Vérifier que le montant total a été calculé
        $this->assertNotNull($savedLocation->getMontantTotal());
    }

    // ========== TESTS AVEC USER ==========

    public function testFillFromUser(): void
    {
        $user = $this->createValidUser();
        $location = new Location();
        $location->fillFromUser($user);
        
        $this->assertEquals($user, $location->getUser());
        $this->assertEquals('Dupont Jean', $location->getClientNomComplet());
        $this->assertEquals('12345678', $location->getClientTelephone());
    }

    public function testLocationWithUserInfo(): void
    {
        $location = $this->createValidLocation();
        
        $this->assertNotNull($location->getUser());
        $this->assertNotNull($location->getClientNomComplet());
        $this->assertNotNull($location->getClientTelephone());
    }
}