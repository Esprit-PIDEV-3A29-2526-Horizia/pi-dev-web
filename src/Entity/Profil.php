<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\User;

#[ORM\Entity]
class Profil
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 30)]
    private string $type;

    #[ORM\Column(type: "string", length: 30)]
    private string $statut;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    #[ORM\OneToMany(mappedBy: "profil_id", targetEntity: User::class)]
    private Collection $users;

        public function getUsers(): Collection
        {
            return $this->users;
        }
    
        public function addUser(User $user): self
        {
            if (!$this->users->contains($user)) {
                $this->users[] = $user;
                $user->setProfil_id($this);
            }
    
            return $this;
        }
    
        public function removeUser(User $user): self
        {
            if ($this->users->removeElement($user)) {
                // set the owning side to null (unless already changed)
                if ($user->getProfil_id() === $this) {
                    $user->setProfil_id(null);
                }
            }
    
            return $this;
        }
}
