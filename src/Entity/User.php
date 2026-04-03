<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $addresse = null;

    #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'profil_id', referencedColumnName: 'id')]
    private ?Profil $profil = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $face_descriptor = null;

    #[ORM\OneToMany(targetEntity: Reservationlog::class, mappedBy: 'user')]
    private Collection $reservationlogs;

    public function __construct()
    {
        $this->reservationlogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAddresse(): ?string
    {
        return $this->addresse;
    }

    public function setAddresse(?string $addresse): self
    {
        $this->addresse = $addresse;
        return $this;
    }

    public function getProfil(): ?Profil
    {
        return $this->profil;
    }

    public function setProfil(?Profil $profil): self
    {
        $this->profil = $profil;
        return $this;
    }

    public function getFaceDescriptor(): ?string
    {
        return $this->face_descriptor;
    }

    public function setFaceDescriptor(?string $face_descriptor): self
    {
        $this->face_descriptor = $face_descriptor;
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
            $reservationlog->setUser($this);
        }
        return $this;
    }

    public function removeReservationlog(Reservationlog $reservationlog): self
    {
        if ($this->reservationlogs->removeElement($reservationlog)) {
            if ($reservationlog->getUser() === $this) {
                $reservationlog->setUser(null);
            }
        }
        return $this;
    }
}