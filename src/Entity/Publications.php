<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'publications')]
#[ORM\Entity]
class Publications
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id = 0;

    #[ORM\Column(name: 'titre', type: 'string', length: 255, nullable: false)]
    private string $titre;

    #[ORM\Column(name: 'description', type: 'text', length: 65535, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'image', type: 'string', length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'categorie', type: 'string', length: 50, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(name: 'utilisateur_id', type: 'integer', nullable: true)]
    private ?int $utilisateurId = null;

    #[ORM\Column(name: 'auteur', type: 'string', length: 100, nullable: true)]
    private ?string $auteur = null;

    #[ORM\Column(name: 'likes', type: 'integer', nullable: true)]
    private ?int $likes = null;

    #[ORM\Column(name: 'commentaires', type: 'integer', nullable: true)]
    private ?int $commentaires = null;

    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    private \DateTimeInterface $dateCreation;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateurId;
    }

    public function setUtilisateurId(?int $utilisateurId): self
    {
        $this->utilisateurId = $utilisateurId;
        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): self
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(?int $likes): self
    {
        $this->likes = $likes;
        return $this;
    }

    public function getCommentaires(): ?int
    {
        return $this->commentaires;
    }

    public function setCommentaires(?int $commentaires): self
    {
        $this->commentaires = $commentaires;
        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }
}