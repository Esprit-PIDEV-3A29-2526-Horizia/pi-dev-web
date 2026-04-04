<?php

namespace App\Form;

use App\Entity\Events;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr'  => ['class' => 'form-control', 'placeholder' => "Titre de l'événement"]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Description détaillée']
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: Concert, Conférence, Atelier'],
                'empty_data' => 'Genéral'
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Adresse ou lieu']
            ])
            ->add('date_debut', DateTimeType::class, [
                'label'         => 'Date de début',
                'widget'        => 'single_text',
                'property_path' => 'date_debut',
                'attr'          => ['class' => 'form-control']
            ])
            ->add('date_fin', DateTimeType::class, [
                'label'         => 'Date de fin',
                'widget'        => 'single_text',
                'property_path' => 'date_fin',
                'attr'          => ['class' => 'form-control']
            ])
            ->add('prix', TextType::class, [
                'label' => 'Prix (TND)',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: 50.000']
            ])
            ->add('capacite_max', NumberType::class, [
                'label'         => 'Capacité maximale',
                'property_path' => 'capacite_max',
                'attr'          => ['class' => 'form-control']
            ])
            ->add('image_url', TextType::class, [
                'label'         => "URL de l'image",
                'required'      => false,
                'property_path' => 'image_url',
                'attr'          => ['class' => 'form-control', 'placeholder' => 'https://example.com/image.jpg']
            ])
            ->add('statut', TextType::class, [
                'label' => 'Statut',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'à venir, en cours, terminé']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
        ]);
    }
}