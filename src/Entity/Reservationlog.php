<?php
// src/Entity/Reservationlog.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReservationlogRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationlogRepository::class)]
#[ORM\Table(name: 'reservationlog')]
class Reservationlog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idreslog = null;

    #[ORM\ManyToOne(targetEntity: Logement::class, inversedBy: 'reservationlogs')]
    #[ORM\JoinColumn(name: 'idlog', referencedColumnName: 'id')]
    private ?Logement $logement = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservationlogs')]
    #[ORM\JoinColumn(name: 'idc', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: "La date d'arrivée est obligatoire.")]
    #[Assert\GreaterThanOrEqual(value: "today", message: "La date d'arrivée ne peut pas être dans le passé.")]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: "La date de départ est obligatoire.")]
    #[Assert\GreaterThan(propertyPath: "date_debut", message: "La date de départ doit être postérieure à la date d'arrivée.")]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(type: 'float', nullable: false)]
    #[Assert\NotNull(message: "Le montant est obligatoire.")]
    #[Assert\Positive(message: "Le montant doit être positif.")]
    private ?float $montant = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Choice(choices: ['En ligne', 'Sur place'], message: "Modalité invalide.")]
    private ?string $modalites = null;

    // NOUVEAUX CHAMPS
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Assert\NotNull(message: "Le nombre d'adultes est obligatoire.")]
    #[Assert\Positive(message: "Le nombre d'adultes doit être positif.")]
    private int $adultes = 1;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\NotNull(message: "Le nombre d'enfants est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le nombre d'enfants doit être zéro ou positif.")]
    private int $enfants = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Assert\NotNull(message: "Le nombre de chambres est obligatoire.")]
    #[Assert\Positive(message: "Le nombre de chambres doit être positif.")]
    private int $nombre_Chambres = 1;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    // Supprimé : #[Assert\NotNull]
    #[Assert\Choice(choices: ['all_inclusive', 'demi_pension', 'petit_dejeuner', 'soft'], message: "Type de pension invalide.")]
    private ?string $mode_Reservation = null;
#[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
private ?\DateTimeInterface $createdAt = null;

public function getCreatedAt(): ?\DateTimeInterface
{
    return $this->createdAt;
}

public function setCreatedAt(\DateTimeInterface $createdAt): self
{
    $this->createdAt = $createdAt;
    return $this;
}
#[ORM\Column(type: 'string', length: 255, nullable: true)]
private ?string $repartition_Chambres = null;

public function getRepartitionChambres(): ?string { return $this->repartition_Chambres; }
public function setRepartitionChambres(?string $repartitionChambres): self { $this->repartition_Chambres = $repartitionChambres; return $this; }
    // Getters & Setters (existants + nouveaux)
    public function getIdreslog(): ?int { return $this->idreslog; }
    public function setIdreslog(int $idreslog): self { $this->idreslog = $idreslog; return $this; }

    public function getLogement(): ?Logement { return $this->logement; }
    public function setLogement(?Logement $logement): self { $this->logement = $logement; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->date_debut; }
    public function setDateDebut(\DateTimeInterface $date_debut): self { $this->date_debut = $date_debut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->date_fin; }
    public function setDateFin(\DateTimeInterface $date_fin): self { $this->date_fin = $date_fin; return $this; }

    public function getMontant(): ?float { return $this->montant; }
    public function setMontant(float $montant): self { $this->montant = $montant; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getModalites(): ?string { return $this->modalites; }
    public function setModalites(?string $modalites): self { $this->modalites = $modalites; return $this; }

    public function getAdultes(): int { return $this->adultes; }
    public function setAdultes(int $adultes): self { $this->adultes = $adultes; return $this; }

    public function getEnfants(): int { return $this->enfants; }
    public function setEnfants(int $enfants): self { $this->enfants = $enfants; return $this; }

    public function getNombreChambres(): int { return $this->nombre_Chambres; }
    public function setNombreChambres(int $nombreChambres): self { $this->nombre_Chambres = $nombreChambres; return $this; }

    public function getModeReservation(): ?string { return $this->mode_Reservation; }
    public function setModeReservation(?string $modeReservation): self { $this->mode_Reservation = $modeReservation; return $this; }
}