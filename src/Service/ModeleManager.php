<?php

namespace App\Service;

use App\Entity\Modele;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class ModeleManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Règles métier pour Modele :
     * 1. Le nom du modèle est obligatoire
     * 2. Le nom ne doit pas dépasser 80 caractères
     * 3. Le modèle doit être associé à une marque
     */
    public function validate(Modele $modele): bool
    {
        // Règle 1 : Nom obligatoire
        $nom = trim((string) $modele->getNomModele());
        if (empty($nom)) {
            throw new InvalidArgumentException('Le nom du modèle est obligatoire.');
        }

        // Règle 2 : Longueur max 80
        if (strlen($nom) > 80) {
            throw new InvalidArgumentException('Le nom du modèle ne doit pas dépasser 80 caractères.');
        }

        // Règle 3 : Marque obligatoire
        if ($modele->getMarque() === null) {
            throw new InvalidArgumentException('Le modèle doit être associé à une marque.');
        }

        return true;
    }

    public function save(Modele $modele): Modele
    {
        $this->validate($modele);
        $this->entityManager->persist($modele);
        $this->entityManager->flush();
        return $modele;
    }

    public function delete(Modele $modele): void
    {
        // Vérifier si le modèle a des véhicules
        if ($modele->getVehicules()->count() > 0) {
            throw new InvalidArgumentException('Impossible de supprimer un modèle qui a des véhicules associés.');
        }
        $this->entityManager->remove($modele);
        $this->entityManager->flush();
    }
}