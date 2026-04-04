<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Participation;

#[ORM\Entity]
class Events
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_event;

    #[ORM\Column(type: "string", length: 150)]
    private string $titre;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 150)]
    private string $categorie;

    #[ORM\Column(type: "string", length: 150)]
    private string $location;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_debut;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_fin;

    #[ORM\Column(type: "string")]
    private string $prix;

    #[ORM\Column(type: "integer")]
    private int $capacite_max;

    #[ORM\Column(type: "integer")]
    private int $places_restantes;

    #[ORM\Column(type: "string", length: 255)]
    private string $image_url;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut;

    #[ORM\Column(type: "integer")]
    private int $id_createur;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId_event()
    {
        return $this->id_event;
    }

    public function setId_event($value)
    {
        $this->id_event = $value;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($value)
    {
        $this->titre = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getCategorie()
    {
        return $this->categorie;
    }

    public function setCategorie($value)
    {
        $this->categorie = $value;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation($value)
    {
        $this->location = $value;
    }

    public function getDate_debut()
    {
        return $this->date_debut;
    }

    public function setDate_debut($value)
    {
        $this->date_debut = $value;
    }

    public function getDate_fin()
    {
        return $this->date_fin;
    }

    public function setDate_fin($value)
    {
        $this->date_fin = $value;
    }

    public function getPrix()
    {
        return $this->prix;
    }

    public function setPrix($value)
    {
        $this->prix = $value;
    }

    public function getCapacite_max()
    {
        return $this->capacite_max;
    }

    public function setCapacite_max($value)
    {
        $this->capacite_max = $value;
    }

    public function getPlaces_restantes()
    {
        return $this->places_restantes;
    }

    public function setPlaces_restantes($value)
    {
        $this->places_restantes = $value;
    }

    public function getImage_url()
    {
        return $this->image_url;
    }

    public function setImage_url($value)
    {
        $this->image_url = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    public function getId_createur()
    {
        return $this->id_createur;
    }

    public function setId_createur($value)
    {
        $this->id_createur = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_event", targetEntity: Participation::class)]
    private Collection $participations;

        public function getParticipations(): Collection
        {
            return $this->participations;
        }
    
        public function addParticipation(Participation $participation): self
        {
            if (!$this->participations->contains($participation)) {
                $this->participations[] = $participation;
                $participation->setId_event($this);
            }
    
            return $this;
        }
    
        public function removeParticipation(Participation $participation): self
        {
            if ($this->participations->removeElement($participation)) {
                // set the owning side to null (unless already changed)
                if ($participation->getId_event() === $this) {
                    $participation->setId_event(null);
                }
            }
    
            return $this;
        }
}
