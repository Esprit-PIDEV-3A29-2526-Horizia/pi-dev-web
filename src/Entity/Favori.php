<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FavoriRepository;

#[ORM\Entity(repositoryClass: FavoriRepository::class)]
#[ORM\Table(name: 'favoris')]
class Favori
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $utilisateur_id = null;

    public function getUtilisateur_id(): ?int
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateur_id(int $utilisateur_id): self
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $publication_id = null;

    public function getPublication_id(): ?int
    {
        return $this->publication_id;
    }

    public function setPublication_id(int $publication_id): self
    {
        $this->publication_id = $publication_id;
        return $this;
    }

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateur_id;
    }

    public function getPublicationId(): ?int
    {
        return $this->publication_id;
    }

    public function setPublicationId(int $publication_id): static
    {
        $this->publication_id = $publication_id;

        return $this;
    }

}
