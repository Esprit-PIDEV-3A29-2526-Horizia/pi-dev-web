<?php

namespace App\Form;

use App\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'mapped'      => false,
                'label'       => 'Prénom',
                'constraints' => [new NotBlank(['message' => 'Le prénom est obligatoire.'])],
                'attr'        => ['class' => 'form-control', 'placeholder' => 'Votre prénom']
            ])
            ->add('nom', TextType::class, [
                'mapped'      => false,
                'label'       => 'Nom',
                'constraints' => [new NotBlank(['message' => 'Le nom est obligatoire.'])],
                'attr'        => ['class' => 'form-control', 'placeholder' => 'Votre nom']
            ])
            ->add('email', EmailType::class, [
                'mapped'      => false,
                'label'       => 'Email',
                'constraints' => [
                    new NotBlank(['message' => "L'email est obligatoire."]),
                    new Email(['message' => "L'email n'est pas valide."])
                ],
                'attr'        => ['class' => 'form-control', 'placeholder' => 'votre@email.com']
            ])
            ->add('telephone', TelType::class, [
                'mapped'      => false,
                'label'       => 'Téléphone',
                'constraints' => [new NotBlank(['message' => 'Le téléphone est obligatoire.'])],
                'attr'        => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX']
            ])
            ->add('nombre_places', IntegerType::class, [
                'label'       => 'Nombre de places',
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de places est obligatoire.']),
                    new Positive(['message' => 'Le nombre de places doit être positif.'])
                ],
                'attr'        => [
                    'class'   => 'form-control',
                    'min'     => 1,
                    'id'      => 'nombre_places'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
        ]);
    }
}