<?php

namespace App\Service;

use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class VehiculeManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Règles métier pour Vehicule :
     * 1. L'immatriculation est obligatoire
     * 2. Le prix par jour doit être > 0
     * 3. L'année doit être valide (1900 - année courante)
     * 4. Le kilométrage ne doit pas être négatif
     * 5. Le modèle est obligatoire
     */
    public function validate(Vehicule $vehicule): bool
    {
        // Règle 1 : Immatriculation obligatoire
        $immat = trim((string) $vehicule->getImmatriculation());
        if (empty($immat)) {
            throw new InvalidArgumentException("L'immatriculation est obligatoire.");
        }

        // Règle 2 : Prix positif
        $prix = (float) $vehicule->getPrixParJour();
        if ($prix <= 0) {
            throw new InvalidArgumentException('Le prix par jour doit être supérieur à 0.');
        }

        // Règle 3 : Année valide
        $annee = $vehicule->getAnnee();
        $anneeCourante = (int) date('Y');
        if ($annee < 1900 || $annee > $anneeCourante) {
            throw new InvalidArgumentException("L'année doit être comprise entre 1900 et $anneeCourante.");
        }

        // Règle 4 : Kilométrage non négatif
        $km = $vehicule->getKilometrage();
        if ($km < 0) {
            throw new InvalidArgumentException('Le kilométrage ne peut pas être négatif.');
        }

        // Règle 5 : Modèle obligatoire
        if ($vehicule->getModele() === null) {
            throw new InvalidArgumentException('Le véhicule doit être associé à un modèle.');
        }

        return true;
    }

    public function save(Vehicule $vehicule): Vehicule
    {
        $this->validate($vehicule);
        $this->entityManager->persist($vehicule);
        $this->entityManager->flush();
        return $vehicule;
    }

    public function delete(Vehicule $vehicule): void
    {
        // Vérifier si le véhicule a des locations
        if ($vehicule->getLocations()->count() > 0) {
            throw new InvalidArgumentException('Impossible de supprimer un véhicule qui a des locations.');
        }
        $this->entityManager->remove($vehicule);
        $this->entityManager->flush();
    }

    public function updateDisponibilite(Vehicule $vehicule, string $etat): Vehicule
    {
        $etatsValides = ['disponible', 'loue', 'en_maintenance'];
        if (!in_array($etat, $etatsValides)) {
            throw new InvalidArgumentException("L'état doit être: " . implode(', ', $etatsValides));
        }
        $vehicule->setEtat($etat);
        $this->entityManager->flush();
        return $vehicule;
    }
}