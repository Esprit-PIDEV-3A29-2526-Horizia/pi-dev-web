<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Reservationlog
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idreslog;

    #[ORM\Column(type: "integer")]
    private int $idlog;

    #[ORM\Column(type: "integer")]
    private int $idc;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_debut;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_fin;

    #[ORM\Column(type: "string")]
    private string $montant;

    #[ORM\Column(type: "string", length: 50)]
    private string $status;

    #[ORM\Column(type: "string", length: 255)]
    private string $modalites;

    public function getIdreslog()
    {
        return $this->idreslog;
    }

    public function setIdreslog($value)
    {
        $this->idreslog = $value;
    }

    public function getIdlog()
    {
        return $this->idlog;
    }

    public function setIdlog($value)
    {
        $this->idlog = $value;
    }

    public function getIdc()
    {
        return $this->idc;
    }

    public function setIdc($value)
    {
        $this->idc = $value;
    }

    public function getDate_debut()
    {
        return $this->date_debut;
    }

    public function setDate_debut($value)
    {
        $this->date_debut = $value;
    }

    public function getDate_fin()
    {
        return $this->date_fin;
    }

    public function setDate_fin($value)
    {
        $this->date_fin = $value;
    }

    public function getMontant()
    {
        return $this->montant;
    }

    public function setMontant($value)
    {
        $this->montant = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getModalites()
    {
        return $this->modalites;
    }

    public function setModalites($value)
    {
        $this->modalites = $value;
    }
}
