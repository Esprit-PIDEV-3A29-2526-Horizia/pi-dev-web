<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
#[ORM\Table(name: 'logement')]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Assert\NotBlank(message: "Le type est obligatoire")]
    private ?string $type = null;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "Le nom doit contenir au moins {{ limit }} caractères")]
    #[Assert\Regex(pattern: "/^[A-Z]/", message: "La première lettre du nom doit être une majuscule")]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "L'adresse doit contenir au moins {{ limit }} caractères")]
    #[Assert\Regex(pattern: "/^[A-Z]/", message: "La première lettre de l'adresse doit être une majuscule")]
    private ?string $adresse = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: "La capacité est obligatoire")]
    #[Assert\Positive(message: "La capacité doit être un nombre positif")]
    private ?int $capacite = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "Les équipements est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "Les équipements doivent décrire au moins {{ limit }} caractères")]
    #[Assert\Regex(pattern: "/^[A-Z]/", message: "La première lettre des équipements doit être une majuscule")]
    private ?string $equipement = null;

    #[ORM\Column(type: 'float', nullable: false)]
    #[Assert\NotBlank(message: "Le tarif est obligatoire")]
    #[Assert\Positive(message: "Le tarif doit être un nombre positif")]
    private ?float $tarif_nuit = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    #[Assert\IsTrue(message: "Le logement doit être disponible", groups: ["create"])]
    private ?bool $disponibilite = true;

    // RELATION : l'utilisateur (admin) qui a créé le logement
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(targetEntity: Reservationlog::class, mappedBy: 'logement')]
    /**
     * @var Collection<int, Reservationlog>
     */
    private Collection $reservationlogs;

    public function __construct()
    {
        $this->disponibilite = true;
        $this->reservationlogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): self
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getEquipement(): ?string
    {
        return $this->equipement;
    }

    public function setEquipement(?string $equipement): self
    {
        $this->equipement = $equipement;
        return $this;
    }

    public function getTarifNuit(): ?float
    {
        return $this->tarif_nuit;
    }

    public function setTarifNuit(float $tarif_nuit): self
    {
        $this->tarif_nuit = $tarif_nuit;
        return $this;
    }

    public function isDisponibilite(): ?bool
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(bool $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, Reservationlog>
     */
    public function getReservationlogs(): Collection
    {
        return $this->reservationlogs;
    }

    public function addReservationlog(Reservationlog $reservationlog): self
    {
        if (!$this->reservationlogs->contains($reservationlog)) {
            $this->reservationlogs->add($reservationlog);
            $reservationlog->setLogement($this);
        }
        return $this;
    }

    public function removeReservationlog(Reservationlog $reservationlog): self
    {
        if ($this->reservationlogs->removeElement($reservationlog)) {
            if ($reservationlog->getLogement() === $this) {
                $reservationlog->setLogement(null);
            }
        }
        return $this;
    }
}