<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: "commentaire")]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commentaireList')]
    #[ORM\JoinColumn(name: "publication_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Publication $publication = null;

    #[ORM\Column(nullable: true)]
    private ?int $utilisateur_id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $auteur = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le commentaire ne peut pas être vide.")]
    #[Assert\Length(min: 2, max: 500, minMessage: "Le commentaire doit comporter au moins 2 caractères.", maxMessage: "Le commentaire ne peut pas dépasser 500 caractères.")]
    private string $contenu ='';

    #[ORM\Column]
    private \DateTimeImmutable $date_creation;

    #[ORM\Column(nullable: true)]
    private ?bool $modifie = false;
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: "created_by_id", referencedColumnName: "id", nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->date_creation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;
        return $this;
    }

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateurId(?int $utilisateur_id): static
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeImmutable $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function isModifie(): ?bool
    {
        return $this->modifie;
    }

    public function setModifie(?bool $modifie): static
    {
        $this->modifie = $modifie;
        return $this;
    }
    public function getCreatedBy(): ?User
    {
    return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
    $this->createdBy = $createdBy;
    return $this;
    }

}