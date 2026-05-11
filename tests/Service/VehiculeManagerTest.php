<?php

namespace App\Tests\Service;

use App\Entity\Marque;
use App\Entity\Modele;
use App\Entity\Vehicule;
use App\Service\VehiculeManager;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VehiculeManagerTest extends TestCase
{
    private $entityManager;
    private VehiculeManager $vehiculeManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->vehiculeManager = new VehiculeManager($this->entityManager);
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
        $vehicule->setAnnee(2020);
        $vehicule->setCarburant('Essence');
        $vehicule->setKilometrage(50000);
        $vehicule->setPrixParJour('150.000');
        $vehicule->setEtat('disponible');
        
        return $vehicule;
    }

    public function testValidVehicule(): void
    {
        $vehicule = $this->createValidVehicule();
        $result = $this->vehiculeManager->validate($vehicule);
        $this->assertTrue($result);
    }

    public function testVehiculeWithoutImmatriculation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'immatriculation est obligatoire.");
        
        $vehicule = $this->createValidVehicule();
        $vehicule->setImmatriculation('');
        
        $this->vehiculeManager->validate($vehicule);
    }

    public function testVehiculePrixNegatif(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix par jour doit être supérieur à 0.');
        
        $vehicule = $this->createValidVehicule();
        $vehicule->setPrixParJour('0');
        
        $this->vehiculeManager->validate($vehicule);
    }

    public function testVehiculeAnneeInvalide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $vehicule = $this->createValidVehicule();
        $vehicule->setAnnee(1800);
        
        $this->vehiculeManager->validate($vehicule);
    }

    public function testVehiculeKilometrageNegatif(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le kilométrage ne peut pas être négatif.');
        
        $vehicule = $this->createValidVehicule();
        $vehicule->setKilometrage(-100);
        
        $this->vehiculeManager->validate($vehicule);
    }

    public function testVehiculeWithoutModele(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le véhicule doit être associé à un modèle.');
        
        $vehicule = $this->createValidVehicule();
        $vehicule->setModele(null);
        
        $this->vehiculeManager->validate($vehicule);
    }

    public function testUpdateDisponibilite(): void
    {
        $vehicule = $this->createValidVehicule();
        $this->vehiculeManager->updateDisponibilite($vehicule, 'loue');
        $this->assertEquals('loue', $vehicule->getEtat());
    }

    public function testUpdateDisponibiliteInvalide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $vehicule = $this->createValidVehicule();
        $this->vehiculeManager->updateDisponibilite($vehicule, 'invalide');
    }
}