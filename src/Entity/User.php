<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Profil;

#[ORM\Entity]
class User
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "string", length: 100)]
    private string $prenom;

    #[ORM\Column(type: "string", length: 255)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\Column(type: "string", length: 20)]
    private string $telephone;

    #[ORM\Column(type: "string", length: 255)]
    private string $addresse;

        #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: "users")]
    #[ORM\JoinColumn(name: 'profil_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Profil $profil_id;

    #[ORM\Column(type: "text")]
    private string $face_descriptor;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }

    public function setPrenom($value)
    {
        $this->prenom = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($value)
    {
        $this->password = $value;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function setTelephone($value)
    {
        $this->telephone = $value;
    }

    public function getAddresse()
    {
        return $this->addresse;
    }

    public function setAddresse($value)
    {
        $this->addresse = $value;
    }

    public function getProfil_id()
    {
        return $this->profil_id;
    }

    public function setProfil_id($value)
    {
        $this->profil_id = $value;
    }

    public function getFace_descriptor()
    {
        return $this->face_descriptor;
    }

    public function setFace_descriptor($value)
    {
        $this->face_descriptor = $value;
    }
}
