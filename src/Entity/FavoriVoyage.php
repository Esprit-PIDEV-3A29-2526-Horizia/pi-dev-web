<?php

namespace App\Entity;

use App\Repository\FavoriVoyageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriVoyageRepository::class)]
#[ORM\Table(name: 'favorivoyage')]
class FavoriVoyage
{
    #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: 'integer')]
/** @phpstan-ignore-next-line */
private ?int $id = null;

    #[ORM\Column(name: 'visitor_token', type: 'string', length: 100, nullable: true)]
    private ?string $visitorToken = null;

    #[ORM\ManyToOne(targetEntity: Voyage::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Voyage $voyage = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    // RELATION : l'utilisateur (connecté) qui a créé le favori
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVisitorToken(): ?string
    {
        return $this->visitorToken;
    }

    public function setVisitorToken(?string $visitorToken): self
    {
        $this->visitorToken = $visitorToken;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
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
}