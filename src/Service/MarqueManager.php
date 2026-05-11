<?php

namespace App\Service;

use App\Entity\Marque;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class MarqueManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Règles métier pour Marque :
     * 1. Le nom de la marque est obligatoire
     * 2. Le nom de la marque ne doit pas dépasser 50 caractères
     * 3. Le nom de la marque doit être unique
     */
    public function validate(Marque $marque): bool
    {
        // Règle 1 : Nom obligatoire
        $nom = trim((string) $marque->getNomMarque());
        if (empty($nom)) {
            throw new InvalidArgumentException('Le nom de la marque est obligatoire.');
        }

        // Règle 2 : Longueur max 50
        if (strlen($nom) > 50) {
            throw new InvalidArgumentException('Le nom de la marque ne doit pas dépasser 50 caractères.');
        }

        // Règle 3 : Nom unique
        $existing = $this->entityManager
            ->getRepository(Marque::class)
            ->findOneBy(['nomMarque' => $nom]);

        if ($existing && $existing !== $marque) {
            throw new InvalidArgumentException('Une marque avec ce nom existe déjà.');
        }

        return true;
    }

    public function save(Marque $marque): Marque
    {
        $this->validate($marque);
        $this->entityManager->persist($marque);
        $this->entityManager->flush();
        return $marque;
    }

    public function delete(Marque $marque): void
    {
        // Vérifier si la marque a des modèles
        if ($marque->getModeles()->count() > 0) {
            throw new InvalidArgumentException('Impossible de supprimer une marque qui a des modèles associés.');
        }
        $this->entityManager->remove($marque);
        $this->entityManager->flush();
    }
}