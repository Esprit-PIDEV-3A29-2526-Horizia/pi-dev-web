<?php

namespace App\Tests\Service;

use App\Entity\Marque;
use App\Entity\Modele;
use App\Service\ModeleManager;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ModeleManagerTest extends TestCase
{
    private $entityManager;
    private ModeleManager $modeleManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->modeleManager = new ModeleManager($this->entityManager);
    }

    private function createValidModele(): Modele
    {
        $marque = new Marque();
        $marque->setNomMarque('Toyota');
        
        $modele = new Modele();
        $modele->setNomModele('Corolla');
        $modele->setMarque($marque);
        
        return $modele;
    }

    public function testValidModele(): void
    {
        $modele = $this->createValidModele();
        $result = $this->modeleManager->validate($modele);
        $this->assertTrue($result);
    }

    public function testModeleWithoutName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du modèle est obligatoire.');
        
        $modele = new Modele();
        $modele->setNomModele('');
        
        $this->modeleManager->validate($modele);
    }

    public function testModeleNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du modèle ne doit pas dépasser 80 caractères.');
        
        $modele = new Modele();
        $modele->setNomModele(str_repeat('A', 81));
        
        $this->modeleManager->validate($modele);
    }

    public function testModeleWithoutMarque(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le modèle doit être associé à une marque.');
        
        $modele = new Modele();
        $modele->setNomModele('Corolla');
        $modele->setMarque(null);
        
        $this->modeleManager->validate($modele);
    }
}