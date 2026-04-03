<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationRepository;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_reservation = null;

    public function getDate_reservation(): ?\DateTimeInterface
    {
        return $this->date_reservation;
    }

    public function setDate_reservation(\DateTimeInterface $date_reservation): self
    {
        $this->date_reservation = $date_reservation;
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
    private ?int $id_voyage = null;

    public function getId_voyage(): ?int
    {
        return $this->id_voyage;
    }

    public function setId_voyage(int $id_voyage): self
    {
        $this->id_voyage = $id_voyage;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id_user = null;

    public function getId_user(): ?int
    {
        return $this->id_user;
    }

    public function setId_user(?int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbr_personnes = null;

    public function getNbr_personnes(): ?int
    {
        return $this->nbr_personnes;
    }

    public function setNbr_personnes(?int $nbr_personnes): self
    {
        $this->nbr_personnes = $nbr_personnes;
        return $this;
    }

}
