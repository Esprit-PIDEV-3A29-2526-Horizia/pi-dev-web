<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class UserManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Valide les règles métier d'un utilisateur
     * 
     * Règles à valider :
     * 1. Le nom est obligatoire et ne peut pas être vide
     * 2. Le prénom est obligatoire et ne peut pas être vide
     * 3. L'email doit être valide
     * 4. Le mot de passe doit contenir au moins 6 caractères
     * 5. Le téléphone (optionnel) doit avoir au moins 8 chiffres si renseigné
     * 
     * @throws InvalidArgumentException
     */
    public function validate(User $user): bool
    {
        // Règle 1 : Nom obligatoire (trim pour détecter les espaces uniquement)
        $nom = trim((string) $user->getNom());
        if (empty($nom)) {
            throw new InvalidArgumentException('Le nom est obligatoire.');
        }

        // Règle 2 : Prénom obligatoire (trim pour détecter les espaces uniquement)
        $prenom = trim((string) $user->getPrenom());
        if (empty($prenom)) {
            throw new InvalidArgumentException('Le prénom est obligatoire.');
        }

        // Règle 3 : Email valide
        $email = trim((string) $user->getEmail());
        if (empty($email)) {
            throw new InvalidArgumentException('L\'email est obligatoire.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'email doit être valide.');
        }

        // Règle 4 : Mot de passe (uniquement pour les nouveaux utilisateurs)
        $password = (string) $user->getPassword();
        if (!empty($password) && strlen($password) < 6) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins 6 caractères.');
        }

        // Règle 5 : Téléphone (optionnel)
        $telephone = (string) $user->getTelephone();
        if (!empty($telephone) && !preg_match('/^[0-9]{8,}$/', $telephone)) {
            throw new InvalidArgumentException('Le numéro de téléphone doit contenir au moins 8 chiffres.');
        }

        return true;
    }

    /**
     * Sauvegarde un utilisateur après validation
     */
    public function save(User $user): User
    {
        $this->validate($user);
        
        // Mettre à jour le timestamp
        $user->setUpdatedAt(new \DateTime());
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Supprime un utilisateur
     */
    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Active un utilisateur
     */
    public function activate(User $user): User
    {
        // Si vous avez un champ 'actif' dans User, décommentez
        // $user->setActif(true);
        $user->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Désactive un utilisateur
     */
    public function deactivate(User $user): User
    {
        // Si vous avez un champ 'actif' dans User, décommentez
        // $user->setActif(false);
        $user->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
        
        return $user;
    }
}