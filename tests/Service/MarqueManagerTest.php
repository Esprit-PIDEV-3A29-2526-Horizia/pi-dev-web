<?php

namespace App\Tests\Service;

use App\Entity\Marque;
use App\Service\MarqueManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MarqueManagerTest extends TestCase
{
    private $entityManager;
    private $repository;
    private MarqueManager $marqueManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->marqueManager = new MarqueManager($this->entityManager);
    }

    private function createValidMarque(): Marque
    {
        $marque = new Marque();
        $marque->setNomMarque('Toyota');
        return $marque;
    }

    public function testValidMarque(): void
    {
        $marque = $this->createValidMarque();
        
        $this->repository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($this->repository);
        
        $result = $this->marqueManager->validate($marque);
        $this->assertTrue($result);
    }

    public function testMarqueWithoutName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la marque est obligatoire.');
        
        $marque = new Marque();
        $marque->setNomMarque('');
        
        $this->marqueManager->validate($marque);
    }

    public function testMarqueNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la marque ne doit pas dépasser 50 caractères.');
        
        $marque = new Marque();
        $marque->setNomMarque(str_repeat('A', 51));
        
        $this->marqueManager->validate($marque);
    }

    public function testMarqueDuplicateName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Une marque avec ce nom existe déjà.');
        
        $marque = $this->createValidMarque();
        $existingMarque = $this->createValidMarque();
        
        $this->repository->method('findOneBy')->willReturn($existingMarque);
        $this->entityManager->method('getRepository')->willReturn($this->repository);
        
        $this->marqueManager->validate($marque);
    }
}