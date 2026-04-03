<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\EventRepository;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_event = null;

    public function getId_event(): ?int
    {
        return $this->id_event;
    }

    public function setId_event(int $id_event): self
    {
        $this->id_event = $id_event;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $categorie = null;

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $location = null;

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_debut = null;

    public function getDate_debut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDate_debut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_fin = null;

    public function getDate_fin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDate_fin(\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private $prix;

    public function getPrix()
    {
        return $this->prix;
    }

    public function setPrix($prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $capacite_max = null;

    public function getCapacite_max(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapacite_max(int $capacite_max): self
    {
        $this->capacite_max = $capacite_max;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $image_url = null;

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(string $image_url): self
    {
        $this->image_url = $image_url;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_createur = null;

    public function getId_createur(): ?int
    {
        return $this->id_createur;
    }

    public function setId_createur(int $id_createur): self
    {
        $this->id_createur = $id_createur;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}

