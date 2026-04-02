<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VoyageRepository;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: 'voyage')]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $destination = null;

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $prix = null;

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_depart = null;

    public function getDate_depart(): ?\DateTimeInterface
    {
        return $this->date_depart;
    }

    public function setDate_depart(\DateTimeInterface $date_depart): self
    {
        $this->date_depart = $date_depart;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_retour = null;

    public function getDate_retour(): ?\DateTimeInterface
    {
        return $this->date_retour;
    }

    public function setDate_retour(\DateTimeInterface $date_retour): self
    {
        $this->date_retour = $date_retour;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image_url = null;

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(?string $image_url): self
    {
        $this->image_url = $image_url;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id_categorie = null;

    public function getId_categorie(): ?int
    {
        return $this->id_categorie;
    }

    public function setId_categorie(?int $id_categorie): self
    {
        $this->id_categorie = $id_categorie;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $places_total = null;

    public function getPlaces_total(): ?int
    {
        return $this->places_total;
    }

    public function setPlaces_total(int $places_total): self
    {
        $this->places_total = $places_total;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $places_restantes = null;

    public function getPlaces_restantes(): ?int
    {
        return $this->places_restantes;
    }

    public function setPlaces_restantes(int $places_restantes): self
    {
        $this->places_restantes = $places_restantes;
        return $this;
    }

    public function getDateDepart(): ?\DateTime
    {
        return $this->date_depart;
    }

    public function setDateDepart(\DateTime $date_depart): static
    {
        $this->date_depart = $date_depart;

        return $this;
    }

    public function getDateRetour(): ?\DateTime
    {
        return $this->date_retour;
    }

    public function setDateRetour(\DateTime $date_retour): static
    {
        $this->date_retour = $date_retour;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

    public function getIdCategorie(): ?int
    {
        return $this->id_categorie;
    }

    public function setIdCategorie(?int $id_categorie): static
    {
        $this->id_categorie = $id_categorie;

        return $this;
    }

    public function getPlacesTotal(): ?int
    {
        return $this->places_total;
    }

    public function setPlacesTotal(int $places_total): static
    {
        $this->places_total = $places_total;

        return $this;
    }

    public function getPlacesRestantes(): ?int
    {
        return $this->places_restantes;
    }

    public function setPlacesRestantes(int $places_restantes): static
    {
        $this->places_restantes = $places_restantes;

        return $this;
    }

}
