<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Participation
 *
 * @ORM\Table(name="participation", indexes={@ORM\Index(name="fk_participation_event", columns={"id_event"})})
 * @ORM\Entity
 */
class Participation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_participation", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idParticipation;

    /**
     * @var int
     *
     * @ORM\Column(name="id_event", type="integer", nullable=false)
     */
    private $idEvent;

    /**
     * @var int
     *
     * @ORM\Column(name="nombre_places", type="integer", nullable=false)
     */
    private $nombrePlaces;

    /**
     * @var float
     *
     * @ORM\Column(name="montant_total", type="float", precision=10, scale=0, nullable=false)
     */
    private $montantTotal;

    /**
     * @var string
     *
     * @ORM\Column(name="statut", type="string", length=50, nullable=false, options={"default"="'confirmée'"})
     */
    private $statut = '\'confirmée\'';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_participation", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $dateParticipation = 'CURRENT_TIMESTAMP';


}
