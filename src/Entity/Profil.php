<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
<<<<<<< HEAD
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ProfilRepository;

#[ORM\Entity(repositoryClass: ProfilRepository::class)]
#[ORM\Table(name: 'profil')]
class Profil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
=======

/**
 * Profil
 *
 * @ORM\Table(name="profil")
 * @ORM\Entity
 */
class Profil
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type", type="string", length=30, nullable=true)
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(name="statut", type="string", length=30, nullable=true)
     */
    private $statut;
>>>>>>> origin/gestion_voyage

    public function getId(): ?int
    {
        return $this->id;
    }

<<<<<<< HEAD
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'profil')]
    private Collection $users;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $statut = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        if (!$this->users instanceof Collection) {
            $this->users = new ArrayCollection();
        }
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->getUsers()->contains($user)) {
            $this->getUsers()->add($user);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->getUsers()->removeElement($user);
        return $this;
    }

=======
>>>>>>> origin/gestion_voyage
    public function getType(): ?string
    {
        return $this->type;
    }

<<<<<<< HEAD
    public function setType(?string $type): static
    {
        $this->type = $type;

=======
    public function setType(?string $type): self
    {
        $this->type = $type;
>>>>>>> origin/gestion_voyage
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

<<<<<<< HEAD
    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

}
=======
    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function __toString(): string
    {
        return $this->type ?? 'Profil';
    }
}
>>>>>>> origin/gestion_voyage
