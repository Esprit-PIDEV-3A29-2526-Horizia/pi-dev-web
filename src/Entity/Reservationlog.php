<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReservationlogRepository;

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
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $montant = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $modalites = null;

    public function getIdreslog(): ?int
    {
        return $this->idreslog;
    }

    public function setIdreslog(int $idreslog): self
    {
        $this->idreslog = $idreslog;
        return $this;
    }

    public function getLogement(): ?Logement
    {
        return $this->logement;
    }

    public function setLogement(?Logement $logement): self
    {
        $this->logement = $logement;
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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getModalites(): ?string
    {
        return $this->modalites;
    }

    public function setModalites(?string $modalites): self
    {
        $this->modalites = $modalites;
        return $this;
    }
}