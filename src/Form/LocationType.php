<?php

namespace App\Form;

use App\Entity\Location;
use App\Entity\Vehicule;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vehicule', EntityType::class, [
                'class'        => Vehicule::class,
                'choice_label' => function (Vehicule $v): string {
                    $marque  = $v->getModele()?->getMarque()?->getNomMarque() ?? '';
                    $modele  = $v->getModele()?->getNomModele() ?? '';
                    $immat   = $v->getImmatriculation() ?? '';
                    return trim("$marque $modele — $immat");
                },
                'choice_value' => 'idVehicule',
                'choice_attr'  => function (Vehicule $v): array {
                    return [
                        'data-marque' => $v->getModele()?->getMarque()?->getNomMarque() ?? '',
                        'data-modele' => $v->getModele()?->getNomModele() ?? '',
                        'data-immat'  => $v->getImmatriculation() ?? '',
                        'data-etat'   => $v->getEtat() ?? '',
                        'data-prix'   => $v->getPrixParJour() ?? '',
                    ];
                },
                'label'       => 'Véhicule',
                'placeholder' => '-- Sélectionner un véhicule --',
                'attr'        => ['class' => 'form-control', 'id' => 'vehicule_select'],
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez sélectionner un véhicule.'),
                ],
            ])
            ->add('clientNomComplet', TextType::class, [
                'label' => 'Nom complet',
                'attr'  => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom complet est obligatoire.'),
                    new Assert\Length(
                        min: 3, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
                        max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        message: 'Le nom ne peut contenir que des lettres, espaces et tirets.'
                    ),
                ],
            ])
            ->add('clientTelephone', TextType::class, [
                'label' => 'Téléphone',
                'attr'  => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le téléphone est obligatoire.'),
                    new Assert\Regex(
                        pattern: '/^[0-9\+\s\-]{8,15}$/',
                        message: 'Le numéro de téléphone est invalide (8 à 15 chiffres).'
                    ),
                ],
            ])
            ->add('clientCin', TextType::class, [
                'label'    => 'CIN',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\Length(
                        max: 20, maxMessage: 'Le CIN ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Assert\Regex(
                        pattern: '/^[0-9]{8}$/',
                        message: 'Le CIN doit contenir exactement 8 chiffres.'
                    ),
                ],
            ])
            ->add('clientPermisNumero', TextType::class, [
                'label'    => 'N° Permis',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\Length(
                        max: 20, maxMessage: 'Le numéro de permis ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('clientAdresse', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'id' => 'adresse_input'],
                'constraints' => [
                    new Assert\Length(
                        max: 255, maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères."
                    ),
                ],
            ])
            ->add('clientVille', TextType::class, [
                'label'    => 'Ville',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\Length(
                        max: 100, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('clientCodePostal', TextType::class, [
                'label'    => 'Code postal',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^[0-9]{4,10}$/',
                        message: 'Le code postal doit contenir entre 4 et 10 chiffres.'
                    ),
                ],
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label'  => 'Date début',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control', 'id' => 'date_debut'],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de début est obligatoire.'),
                    new Assert\GreaterThanOrEqual(
                        value: 'today',
                        message: 'La date de début doit être aujourd\'hui ou dans le futur.'
                    ),
                ],
            ])
            ->add('dateFinPrevue', DateTimeType::class, [
                'label'  => 'Date fin prévue',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control', 'id' => 'date_fin'],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de fin est obligatoire.'),
                ],
            ])
            ->add('dateFinReelle', DateTimeType::class, [
                'label'    => 'Date fin réelle',
                'required' => false,
                'widget'   => 'single_text',
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('kilometrageDebut', NumberType::class, [
                'label' => 'Kilométrage départ',
                'attr'  => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le kilométrage de départ est obligatoire.'),
                    new Assert\PositiveOrZero(message: 'Le kilométrage doit être positif ou zéro.'),
                ],
            ])
            ->add('kilometrageRetour', NumberType::class, [
                'label'    => 'Kilométrage retour',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\PositiveOrZero(message: 'Le kilométrage doit être positif ou zéro.'),
                ],
            ])
          ->add('prixParJour', NumberType::class, [
    'label' => 'Prix par jour (TND)',
    'attr'  => ['class' => 'form-control', 'id' => 'prix_par_jour', 'step' => '0.001', 'readonly' => true],
    'constraints' => [
        // new Assert\NotBlank(message: 'Le prix par jour est obligatoire.'), // ← COMMENTEZ cette ligne
        new Assert\Positive(message: 'Le prix doit être supérieur à 0.'),
    ],
])
            ->add('avance', NumberType::class, [
                'label'    => 'Avance (TND)',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\PositiveOrZero(message: "L'avance doit être positive ou zéro."),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Réservée' => 'réservée',
                    'En cours' => 'en_cours',
                    'Terminée' => 'terminée',
                    'Annulée'  => 'annulée',
                    'No show'  => 'no_show',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le statut est obligatoire.'),
                    new Assert\Choice(
                        choices: ['réservée', 'en_cours', 'terminée', 'annulée', 'no_show'],
                        message: 'Statut invalide.'
                    ),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
                'constraints' => [
                    new Assert\Length(
                        max: 1000, maxMessage: 'Les notes ne peuvent pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('clientLatitude', HiddenType::class, [
                'required' => false,
                'mapped'   => false,
            ])
            ->add('clientLongitude', HiddenType::class, [
                'required' => false,
                'mapped'   => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'attr'       => ['novalidate' => 'novalidate'],
        ]);
    }
}