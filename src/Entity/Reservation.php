<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Reservation
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_reservation;

    #[ORM\Column(type: "string")]
    private string $statut;

    #[ORM\Column(type: "integer")]
    private int $id_voyage;

    #[ORM\Column(type: "integer")]
    private int $id_user;

    #[ORM\Column(type: "integer")]
    private int $nbr_personnes;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getDate_reservation()
    {
        return $this->date_reservation;
    }

    public function setDate_reservation($value)
    {
        $this->date_reservation = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    public function getId_voyage()
    {
        return $this->id_voyage;
    }

    public function setId_voyage($value)
    {
        $this->id_voyage = $value;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    public function getNbr_personnes()
    {
        return $this->nbr_personnes;
    }

    public function setNbr_personnes($value)
    {
        $this->nbr_personnes = $value;
    }
}
