<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Entity\Participation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
class Events
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
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
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(type: "string")]
    private string $prix;

    #[ORM\Column(type: "integer")]
    private ?int $capacite_max = null;

    #[ORM\Column(type: "integer")]
    private ?int $places_restantes = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $image_url = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $longitude = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "id_createur", referencedColumnName: "id", nullable: false)]
    private ?User $createur = null;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(mappedBy: "id_event", targetEntity: Participation::class)]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    // Getters and Setters
    public function getId_event(): ?int
    {
        return $this->id_event;
    }

    public function setId_event(int $value): self
    {
        $this->id_event = $value;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $value): self
    {
        $this->titre = $value;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $value): self
    {
        $this->description = $value;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $value): self
    {
        $this->categorie = $value;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $value): self
    {
        $this->location = $value;
        return $this;
    }

    public function getDate_debut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDate_debut(\DateTimeInterface $value): self
    {
        $this->date_debut = $value;
        return $this;
    }

    public function getDate_fin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDate_fin(\DateTimeInterface $value): self
    {
        $this->date_fin = $value;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $value): self
    {
        $this->prix = $value;
        return $this;
    }

    public function getCapacite_max(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapacite_max(int $value): self
    {
        $this->capacite_max = $value;
        return $this;
    }

    public function getPlaces_restantes(): ?int
    {
        return $this->places_restantes;
    }

    public function setPlaces_restantes(int $value): self
    {
        $this->places_restantes = $value;
        return $this;
    }

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(string $value): self
    {
        $this->image_url = $value;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $value): self
    {
        $this->statut = $value;
        return $this;
    }

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $value): self
    {
        $this->created_at = $value;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    // RELATION AVEC USER
    public function getCreateur(): ?User
    {
        return $this->createur;
    }

    public function setCreateur(?User $createur): self
    {
        $this->createur = $createur;
        return $this;
    }

    /** @return Collection<int, Participation> */
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
            if ($participation->getId_event() === $this) {
                $participation->setId_event(null);
            }
        }
        return $this;
    }

    // CamelCase aliases for Symfony Form
    public function getDateDebut(): ?\DateTimeInterface { return $this->date_debut; }
    public function setDateDebut(?\DateTimeInterface $value): self { $this->date_debut = $value; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->date_fin; }
    public function setDateFin(?\DateTimeInterface $value): self { $this->date_fin = $value; return $this; }
    public function getCapaciteMax(): ?int { return $this->capacite_max; }
    public function setCapaciteMax(?int $value): self { $this->capacite_max = $value; return $this; }
    public function getPlacesRestantes(): ?int { return $this->places_restantes; }
    public function setPlacesRestantes(?int $value): self { $this->places_restantes = $value; return $this; }
    public function getImageUrl(): ?string { return $this->image_url; }
    public function setImageUrl(?string $value): self { $this->image_url = $value; return $this; }

    /**
     * @Assert\Callback
     */
    public function validateDates(ExecutionContextInterface $context): void
    {
        $now = new \DateTime();
        if ($this->date_debut && $this->date_debut <= $now) {
            $context->buildViolation('La date de début doit être dans le futur.')
                ->atPath('date_debut')
                ->addViolation();
        }

        if ($this->date_debut && $this->date_fin && $this->date_fin <= $this->date_debut) {
            $context->buildViolation('La date de fin doit être postérieure à la date de début.')
                ->atPath('date_fin')
                ->addViolation();
        }
    }
}