<?php

namespace App\Form;

use App\Entity\Events;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\File;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr'  => ['class' => 'form-control', 'placeholder' => "Titre de l'événement"],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'max' => 150,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Description détaillée'],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire.']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Concert' => 'Concert',
                    'Conférence' => 'Conférence',
                    'Atelier' => 'Atelier',
                    'Festival' => 'Festival',
                    'Sport' => 'Sport',
                    'Théâtre' => 'Théâtre',
                    'Autre' => 'Autre'
                ],
                'attr'  => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire.']),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'La catégorie ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Adresse ou lieu', 'id' => 'event_location'],
                'constraints' => [
                    new NotBlank(['message' => 'Le lieu est obligatoire.'])
                ]
            ])
            ->add('latitude', HiddenType::class, [
                'mapped' => true,
                'attr' => ['id' => 'event_latitude']
            ])
            ->add('longitude', HiddenType::class, [
                'mapped' => true,
                'attr' => ['id' => 'event_longitude']
            ])
            ->add('date_debut', DateTimeType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La date de début est obligatoire.']),
                    new Type(['type' => '\DateTimeInterface', 'message' => 'La date de début doit être une date valide.'])
                ]
            ])
            ->add('date_fin', DateTimeType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La date de fin est obligatoire.']),
                    new Type(['type' => '\DateTimeInterface', 'message' => 'La date de fin doit être une date valide.'])
                ]
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: 50.000', 'step' => '0.01'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire.']),
                    new Positive(['message' => 'Le prix doit être un nombre positif.'])
                ]
            ])
            ->add('capacite_max', NumberType::class, [
                'label' => 'Capacité maximale',
                'attr'  => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'La capacité maximale est obligatoire.']),
                    new Positive(['message' => 'La capacité maximale doit être un nombre positif.'])
                ]
            ])
                        // Image file upload field
            ->add('image_file', FileType::class, [
                'label' => 'Image (fichier)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/gif,image/webp',
                    'id' => 'event_image_file'
                ],
                /*'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF, WEBP)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo',
                    ])
                ]*/
            ])
            ->add('image_url', TextType::class, [
                'label'    => "OU URL de l'image",
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'https://example.com/image.jpg', 'id' => 'event_image_url']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'À venir' => 'à venir',
                    'En cours' => 'en cours',
                    'Terminé' => 'terminé'
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le statut est obligatoire.'])
                ]
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