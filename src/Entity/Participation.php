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
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event', onDelete: 'CASCADE')]
    private Events $id_event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: "integer")]
    private int $nombre_places;

    #[ORM\Column(type: "string")]
    private string $montant_total;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_participation;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $email_snapshot = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $nom_snapshot = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $prenom_snapshot = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $telephone_snapshot = null;

    public function getId_participation()
    {
        return $this->id_participation;
    }

    public function setId_participation($value)
    {
        $this->id_participation = $value;
        return $this;
    }

    public function getId_event()
    {
        return $this->id_event;
    }

    public function setId_event($value)
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

    public function getNombre_places()
    {
        return $this->nombre_places;
    }

    public function setNombre_places($value)
    {
        $this->nombre_places = $value;
        return $this;
    }

    public function getMontant_total()
    {
        return $this->montant_total;
    }

    public function setMontant_total($value)
    {
        $this->montant_total = $value;
        return $this;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
        return $this;
    }

    public function getDate_participation()
    {
        return $this->date_participation;
    }

    public function setDate_participation($value)
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
    public function getNombrePlaces() { return $this->nombre_places; }
    public function setNombrePlaces($value) { $this->nombre_places = $value; return $this; }

    public function getMontantTotal() { return $this->montant_total; }
    public function setMontantTotal($value) { $this->montant_total = $value; return $this; }

    public function getDateParticipation() { return $this->date_participation; }
    public function setDateParticipation($value) { $this->date_participation = $value; return $this; }

    public function getIdParticipation() { return $this->id_participation; }
    public function setIdParticipation($value) { $this->id_participation = $value; return $this; }

    public function getIdEvent() { return $this->id_event; }
    public function setIdEvent($value) { $this->id_event = $value; return $this; }
}