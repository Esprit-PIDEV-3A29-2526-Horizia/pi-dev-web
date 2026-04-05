<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'date_reservation', type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de réservation est obligatoire.')]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(name: 'statut', type: 'string', length: 50, options: ['default' => 'EN_ATTENTE'])]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    private ?string $statut = 'EN_ATTENTE';

    #[ORM\ManyToOne(targetEntity: Voyage::class)]
    #[ORM\JoinColumn(name: 'id_voyage', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le voyage est obligatoire.')]
    private ?Voyage $voyage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'nbr_personnes', type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: 'Le nombre de personnes est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de personnes doit être positif.')]
    private ?int $nbrPersonnes = null;

    #[ORM\Column(name: 'prix_total', type: 'float', nullable: true)]
    private ?float $prixTotal = null;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): self
    {
        $this->voyage = $voyage;
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

    public function getNbrPersonnes(): ?int
    {
        return $this->nbrPersonnes;
    }

    public function setNbrPersonnes(?int $nbrPersonnes): self
    {
        $this->nbrPersonnes = $nbrPersonnes;
        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(?float $prixTotal): self
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }
}