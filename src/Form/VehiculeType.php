<?php

namespace App\Form;

use App\Entity\Vehicule;
use App\Entity\Modele;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('immatriculation', TextType::class, [
                'label' => 'Immatriculation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('modele', EntityType::class, [
                'class' => Modele::class,
                'choice_label' => function($modele) {
                    $marque = $modele->getMarque();
                    return $marque ? $marque->getNomMarque() . ' ' . $modele->getNomModele() : $modele->getNomModele();
                },
                'choice_value' => 'idModele',  // ✅ Spécifier explicitement la propriété ID
                'label' => 'Modèle',
                'attr' => ['class' => 'form-control']
            ])
            ->add('annee', NumberType::class, [
                'label' => 'Année',
                'attr' => ['class' => 'form-control']
            ])
            ->add('carburant', ChoiceType::class, [
                'label' => 'Carburant',
                'choices' => [
                    'Essence' => 'Essence',
                    'Diesel' => 'Diesel',
                    'Hybride' => 'Hybride',
                    'Electrique' => 'Electrique',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('couleur', TextType::class, [
                'label' => 'Couleur',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('kilometrage', NumberType::class, [
                'label' => 'Kilométrage',
                'attr' => ['class' => 'form-control']
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'Disponible' => 'disponible',
                    'Louée' => 'louee',
                    'En maintenance' => 'en_maintenance',
                    'Indisponible' => 'indisponible',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('prixParJour', NumberType::class, [
                'label' => 'Prix par jour (TND)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('photo', TextType::class, [
                'label' => 'URL de la photo',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}