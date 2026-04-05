<?php

namespace App\Form;

use App\Entity\Profil;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom'
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email'
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe'
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false
            ])
            ->add('addresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false
            ])
            ->add('faceDescriptor', TextareaType::class, [
                'label' => 'Face Descriptor',
                'required' => false
            ])
            ->add('profil', EntityType::class, [
                'class' => Profil::class,
                'choice_label' => 'type',
                'label' => 'Profil',
                'required' => false,
                'placeholder' => 'Choisir un profil'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}