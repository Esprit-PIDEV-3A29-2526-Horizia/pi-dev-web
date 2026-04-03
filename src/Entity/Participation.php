<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ParticipationRepository;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participation')]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_participation = null;

    public function getId_participation(): ?int
    {
        return $this->id_participation;
    }

    public function setId_participation(int $id_participation): self
    {
        $this->id_participation = $id_participation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nombre_places = null;

    public function getNombre_places(): ?int
    {
        return $this->nombre_places;
    }

    public function setNombre_places(int $nombre_places): self
    {
        $this->nombre_places = $nombre_places;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $montant_total = null;

    public function getMontant_total(): ?float
    {
        return $this->montant_total;
    }

    public function setMontant_total(float $montant_total): self
    {
        $this->montant_total = $montant_total;
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

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_participation = null;

    public function getDate_participation(): ?\DateTimeInterface
    {
        return $this->date_participation;
    }

    public function setDate_participation(\DateTimeInterface $date_participation): self
    {
        $this->date_participation = $date_participation;
        return $this;
    }

}
