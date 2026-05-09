<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserGettersAndSetters(): void
    {
        $user = new User();
        $user->setNom('Benlahmer')
             ->setPrenom('Khalil')
             ->setEmail('khalil@example.com')
             ->setTelephone('22911520')
             ->setAddresse('61 rue abu kacem chabbi');

        $this->assertEquals('Benlahmer', $user->getNom());
        $this->assertEquals('Khalil', $user->getPrenom());
        $this->assertEquals('khalil@example.com', $user->getEmail());
        $this->assertEquals('22911520', $user->getTelephone());
        $this->assertEquals('61 rue abu kacem chabbi', $user->getAddresse());
    }

    public function testUserDefaults(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
        $this->assertNull($user->getTelephone());
        $this->assertNull($user->getAddresse());
    }
}