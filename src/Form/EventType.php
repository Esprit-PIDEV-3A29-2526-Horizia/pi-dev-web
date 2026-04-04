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
                'attr' => ['class' => 'form-control', 'placeholder' => 'Titre de l\'événement']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Description détaillée']
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Concert, Conférence, Atelier']
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse ou lieu']
            ])
            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_fin', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
                'scale' => 2
            ])
            ->add('capacite_max', NumberType::class, [
                'label' => 'Capacité maximale',
                'attr' => ['class' => 'form-control']
            ])
            ->add('image_url', TextType::class, [
                'label' => 'URL de l\'image',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '/uploads/images/event.jpg']
            ])
            ->add('statut', TextType::class, [
                'label' => 'Statut',
                'attr' => ['class' => 'form-control', 'placeholder' => 'à venir, en cours, terminé']
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