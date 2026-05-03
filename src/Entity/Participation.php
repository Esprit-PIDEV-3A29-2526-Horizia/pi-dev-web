<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Events;

#[ORM\Entity]
class Participation
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_participation;

    #[ORM\ManyToOne(targetEntity: Events::class, inversedBy: "participations")]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event', onDelete: 'CASCADE', nullable: false)]
    private Events $id_event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: "integer")]
    private int $nombre_places;

    #[ORM\Column(type: "string")]
    private string $montant_total;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date_participation;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $email_snapshot = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $nom_snapshot = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $prenom_snapshot = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $telephone_snapshot = null;

    public function __construct()
    {
        $this->date_participation = new \DateTimeImmutable();
    }

    public function getId_participation(): ?int
    {
        return $this->id_participation;
    }

    public function setId_participation(int $value): self
    {
        $this->id_participation = $value;
        return $this;
    }

    public function getId_event(): ?Events
    {
        return $this->id_event;
    }

    public function setId_event(?Events $value): self
    {
        $this->id_event = $value;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getNombre_places(): ?int
    {
        return $this->nombre_places;
    }

    public function setNombre_places(int $value): self
    {
        $this->nombre_places = $value;
        return $this;
    }

    public function getMontant_total(): ?string
    {
        return $this->montant_total;
    }

    public function setMontant_total(string $value): self
    {
        $this->montant_total = $value;
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

    public function getDate_participation(): ?\DateTimeInterface
    {
        return $this->date_participation;
    }

    public function setDate_participation(?\DateTimeInterface $value): self
    {
        $this->date_participation = $value;
        return $this;
    }

    public function getEmailSnapshot(): ?string
    {
        return $this->email_snapshot;
    }

    public function setEmailSnapshot(?string $email_snapshot): self
    {
        $this->email_snapshot = $email_snapshot;
        return $this;
    }

    public function getNomSnapshot(): ?string
    {
        return $this->nom_snapshot;
    }

    public function setNomSnapshot(?string $nom_snapshot): self
    {
        $this->nom_snapshot = $nom_snapshot;
        return $this;
    }

    public function getPrenomSnapshot(): ?string
    {
        return $this->prenom_snapshot;
    }

    public function setPrenomSnapshot(?string $prenom_snapshot): self
    {
        $this->prenom_snapshot = $prenom_snapshot;
        return $this;
    }

    public function getTelephoneSnapshot(): ?string
    {
        return $this->telephone_snapshot;
    }

    public function setTelephoneSnapshot(?string $telephone_snapshot): self
    {
        $this->telephone_snapshot = $telephone_snapshot;
        return $this;
    }

    // camelCase aliases for Symfony Form
    public function getNombrePlaces(): ?int { return $this->nombre_places; }
    public function setNombrePlaces(int $value): self { $this->nombre_places = $value; return $this; }
    public function getMontantTotal(): ?string { return $this->montant_total; }
    public function setMontantTotal(string $value): self { $this->montant_total = $value; return $this; }
    public function getDateParticipation(): ?\DateTimeInterface { return $this->date_participation; }
    public function setDateParticipation(\DateTimeInterface $value): self { $this->date_participation = $value; return $this; }
    public function getIdParticipation(): ?int { return $this->id_participation; }
    public function setIdParticipation(int $value): self { $this->id_participation = $value; return $this; }
    public function getIdEvent(): ?Events { return $this->id_event; }
    public function setIdEvent(?Events $value): self { $this->id_event = $value; return $this; }
}