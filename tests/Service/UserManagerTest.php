<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $userManager;
    private $entityManager;

    protected function setUp(): void
    {
        // Créer un mock de l'EntityManager
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userManager = new UserManager($this->entityManager);
    }

    /**
     * Test 1: Utilisateur valide - doit passer la validation
     */
    public function testValidUser(): void
    {
        $user = $this->createValidUser();
        
        $result = $this->userManager->validate($user);
        
        $this->assertTrue($result);
    }

    /**
     * Test 2: Utilisateur sans nom - doit échouer
     */
    public function testUserWithoutName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire.');
        
        $user = $this->createValidUser();
        $user->setNom('');
        
        $this->userManager->validate($user);
    }

    /**
     * Test 3: Utilisateur sans prénom - doit échouer
     */
    public function testUserWithoutFirstName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire.');
        
        $user = $this->createValidUser();
        $user->setPrenom('');
        
        $this->userManager->validate($user);
    }

    /**
     * Test 4: Utilisateur avec email invalide - doit échouer
     */
    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email doit être valide.');
        
        $user = $this->createValidUser();
        $user->setEmail('email_invalide');
        
        $this->userManager->validate($user);
    }

    /**
     * Test 5: Utilisateur avec email vide - doit échouer
     */
    public function testUserWithEmptyEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email est obligatoire.');
        
        $user = $this->createValidUser();
        $user->setEmail('');
        
        $this->userManager->validate($user);
    }

    /**
     * Test 6: Utilisateur avec mot de passe trop court - doit échouer
     */
    public function testUserWithShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 6 caractères.');
        
        $user = $this->createValidUser();
        $user->setPassword('12345');
        
        $this->userManager->validate($user);
    }

    /**
     * Test 7: Utilisateur avec téléphone invalide - doit échouer
     */
    public function testUserWithInvalidPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone doit contenir au moins 8 chiffres.');
        
        $user = $this->createValidUser();
        $user->setTelephone('12345'); // Moins de 8 chiffres
        
        $this->userManager->validate($user);
    }

    /**
     * Test 8: Utilisateur avec téléphone valide - doit passer
     */
    public function testUserWithValidPhone(): void
    {
        $user = $this->createValidUser();
        $user->setTelephone('12345678'); // 8 chiffres
        
        $result = $this->userManager->validate($user);
        
        $this->assertTrue($result);
    }

    /**
     * Test 9: Sauvegarde d'un utilisateur valide
     */
    public function testSaveValidUser(): void
    {
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $user = $this->createValidUser();
        $savedUser = $this->userManager->save($user);
        
        $this->assertSame($user, $savedUser);
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getUpdatedAt());
    }

    /**
     * Test 10: Suppression d'un utilisateur
     */
    public function testDeleteUser(): void
    {
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($this->isInstanceOf(User::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $user = $this->createValidUser();
        $this->userManager->delete($user);
        
        $this->assertTrue(true); // Si on arrive ici, le test passe
    }

    /**
     * Test 11: Nom avec espaces uniquement - doit échouer
     */
    public function testUserWithOnlySpacesInName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire.');
        
        $user = $this->createValidUser();
        $user->setNom('   ');
        
        $this->userManager->validate($user);
    }

    /**
     * Test 12: Email avec caractères spéciaux invalides
     */
    public function testUserWithSpecialCharsInEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email doit être valide.');
        
        $user = $this->createValidUser();
        $user->setEmail('test@test@test.com');
        
        $this->userManager->validate($user);
    }

    /**
     * Crée un utilisateur valide pour les tests
     */
    private function createValidUser(): User
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setTelephone('12345678');
        
        return $user;
    }
}