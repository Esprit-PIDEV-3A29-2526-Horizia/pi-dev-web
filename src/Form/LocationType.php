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

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vehicule', EntityType::class, [
                'class'        => Vehicule::class,
                'choice_label' => 'immatriculation',
                'choice_value' => 'idVehicule',
                'label'        => 'Véhicule',
                'attr'         => ['class' => 'form-control', 'id' => 'vehicule_select'],
            ])
            ->add('clientNomComplet', TextType::class, [
                'label' => 'Nom complet',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('clientTelephone', TextType::class, [
                'label' => 'Téléphone',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('clientCin', TextType::class, [
                'label'    => 'CIN',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('clientPermisNumero', TextType::class, [
                'label'    => 'N° Permis',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('clientAdresse', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'id' => 'adresse_input'],
            ])
            ->add('clientVille', TextType::class, [
                'label'    => 'Ville',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('clientCodePostal', TextType::class, [
                'label'    => 'Code postal',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label'  => 'Date début',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control', 'id' => 'date_debut'],
            ])
            ->add('dateFinPrevue', DateTimeType::class, [
                'label'  => 'Date fin prévue',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control', 'id' => 'date_fin'],
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
            ])
            ->add('kilometrageRetour', NumberType::class, [
                'label'    => 'Kilométrage retour',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('prixParJour', NumberType::class, [
                'label' => 'Prix par jour (TND)',
                'attr'  => ['class' => 'form-control', 'id' => 'prix_par_jour', 'step' => '0.001'],
            ])
            ->add('avance', NumberType::class, [
                'label'    => 'Avance (TND)',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
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
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            // ✅ Champs cachés pour la géolocalisation — envoyés par le JS
            ->add('clientLatitude', HiddenType::class, [
                'required' => false,
                'mapped'   => false,  // pas dans l'entité Location
            ])
            ->add('clientLongitude', HiddenType::class, [
                'required' => false,
                'mapped'   => false,  // pas dans l'entité Location
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}