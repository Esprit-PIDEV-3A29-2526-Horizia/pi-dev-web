<?php

namespace App\Form;

use App\Entity\Modele;
use App\Entity\Vehicule;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('immatriculation', TextType::class, [
                'label' => 'Immatriculation',
                'constraints' => [
                    new NotBlank(['message' => 'L\'immatriculation est obligatoire.']),
                    new Regex([
                        'pattern' => '/^[A-Z0-9\-]{4,20}$/i',
                        'message' => 'Format invalide (ex: 123TU4567).',
                    ]),
                ],
            ])
            ->add('modele', EntityType::class, [
                'class'        => Modele::class,
                'choice_label' => fn(Modele $m) => $m->getMarque()?->getNomMarque() . ' – ' . $m->getNomModele(),
                'label'        => 'Modèle',
                'placeholder'  => '-- Choisir un modèle --',
                'required'     => true,
                'constraints'  => [
                    new NotBlank(['message' => 'Le modèle est obligatoire.']),
                ],
            ])
            ->add('annee', IntegerType::class, [
                'label' => 'Année',
                'constraints' => [
                    new NotBlank(['message' => 'L\'année est obligatoire.']),
                    new Range([
                        'min' => 1900,
                        'max' => 2050,
                        'notInRangeMessage' => 'L\'année doit être entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
            ->add('carburant', ChoiceType::class, [
                'label'    => 'Carburant',
                'required' => true, // ✅ empêche null d'atteindre le setter
                'choices'  => [
                    'Essence'    => 'essence',
                    'Diesel'     => 'diesel',
                    'Électrique' => 'electrique',
                    'Hybride'    => 'hybride',
                    'GPL'        => 'gpl',
                ],
                'placeholder' => '-- Choisir --',
                'constraints' => [
                    new NotBlank(['message' => 'Le carburant est obligatoire.']),
                    new Choice([
                        'choices' => ['essence', 'diesel', 'electrique', 'hybride', 'gpl'],
                        'message' => 'Valeur de carburant invalide.',
                    ]),
                ],
            ])
            ->add('couleur', TextType::class, [
                'label'    => 'Couleur',
                'required' => false,
            ])
            ->add('kilometrage', IntegerType::class, [
                'label' => 'Kilométrage',
                'constraints' => [
                    new NotBlank(['message' => 'Le kilométrage est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le kilométrage ne peut pas être négatif.']),
                    new LessThan(['value' => 1000000, 'message' => 'Le kilométrage semble trop élevé.']),
                ],
            ])
            ->add('etat', ChoiceType::class, [
                'label'    => 'État',
                'required' => true, // ✅ même correction que carburant
                'choices'  => [
                    'Disponible'     => 'disponible',
                    'Loué'           => 'loue',
                    'En maintenance' => 'maintenance',
                    'Hors service'   => 'hors_service',
                ],
                'placeholder' => '-- Choisir --',
                'constraints' => [
                    new NotBlank(['message' => 'L\'état est obligatoire.']),
                    new Choice([
                        'choices' => ['disponible', 'loue', 'maintenance', 'hors_service'],
                        'message' => 'État invalide.',
                    ]),
                ],
            ])
            ->add('prixParJour', NumberType::class, [
                'label' => 'Prix par jour (TND)',
                'scale' => 3,
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire.']),
                    new Positive(['message' => 'Le prix doit être positif.']),
                    new LessThan(['value' => 10000, 'message' => 'Le prix semble trop élevé.']),
                ],
            ])
            ->add('photo', FileType::class, [
                'label'    => 'Photo du véhicule',
                'required' => false,
                'mapped'   => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
            'attr'       => ['novalidate' => 'novalidate'],
            'csrf_protection' => true,   // ← vérifier que c'est true
            'csrf_token_id'   => 'vehicule_edit', // ← token id explicite
        ]);
    }
}