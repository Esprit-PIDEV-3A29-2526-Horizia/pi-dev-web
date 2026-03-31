<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Vehicule
 *
 * @ORM\Table(name="vehicule", uniqueConstraints={@ORM\UniqueConstraint(name="immatriculation", columns={"immatriculation"})}, indexes={@ORM\Index(name="id_modele", columns={"id_modele"}), @ORM\Index(name="idx_vehicule_etat", columns={"etat"})})
 * @ORM\Entity
 */
class Vehicule
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_vehicule", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idVehicule;

    /**
     * @var string
     *
     * @ORM\Column(name="immatriculation", type="string", length=20, nullable=false, options={"comment"="ex: 123 TUN 45"})
     */
    private $immatriculation;

    /**
     * @var int
     *
     * @ORM\Column(name="id_modele", type="integer", nullable=false)
     */
    private $idModele;

    /**
     * @var int
     *
     * @ORM\Column(name="annee", type="integer", nullable=false)
     */
    private $annee;

    /**
     * @var string
     *
     * @ORM\Column(name="carburant", type="string", length=0, nullable=false)
     */
    private $carburant;

    /**
     * @var string|null
     *
     * @ORM\Column(name="couleur", type="string", length=30, nullable=true)
     */
    private $couleur;

    /**
     * @var int|null
     *
     * @ORM\Column(name="kilometrage", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $kilometrage = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="etat", type="string", length=0, nullable=true, options={"default"="disponible"})
     */
    private $etat = 'disponible';

    /**
     * @var string
     *
     * @ORM\Column(name="prix_par_jour", type="decimal", precision=10, scale=3, nullable=false, options={"comment"="en TND"})
     */
    private $prixParJour;

    /**
     * @var string|null
     *
     * @ORM\Column(name="photo", type="string", length=255, nullable=true, options={"comment"="chemin ou URL"})
     */
    private $photo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt = 'CURRENT_TIMESTAMP';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt = 'CURRENT_TIMESTAMP';


}
