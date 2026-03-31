<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Reservationlog
 *
 * @ORM\Table(name="reservationlog", indexes={@ORM\Index(name="fk_reservation_logement", columns={"idlog"}), @ORM\Index(name="fk_reservation_user", columns={"idc"})})
 * @ORM\Entity
 */
class Reservationlog
{
    /**
     * @var int
     *
     * @ORM\Column(name="idreslog", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idreslog;

    /**
     * @var int
     *
     * @ORM\Column(name="idlog", type="integer", nullable=false)
     */
    private $idlog;

    /**
     * @var int
     *
     * @ORM\Column(name="idc", type="integer", nullable=false)
     */
    private $idc;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_debut", type="datetime", nullable=false)
     */
    private $dateDebut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin", type="datetime", nullable=false)
     */
    private $dateFin;

    /**
     * @var float
     *
     * @ORM\Column(name="montant", type="float", precision=10, scale=0, nullable=false)
     */
    private $montant;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="modalites", type="string", length=255, nullable=true)
     */
    private $modalites;


}
