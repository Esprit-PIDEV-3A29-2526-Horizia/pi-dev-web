<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) {}

    public function changePassword(User $user, string $newPlainPassword): void
    {
        $hashed = $this->hasher->hashPassword($user, $newPlainPassword);
        $user->setPassword($hashed);
        $this->em->flush();
    }
}