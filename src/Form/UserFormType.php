<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Profil;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom', 'attr' => ['class' => 'form-control']])
            ->add('prenom', TextType::class, ['label' => 'Prénom', 'attr' => ['class' => 'form-control']])
            ->add('email', EmailType::class, ['label' => 'Email', 'attr' => ['class' => 'form-control']])
            ->add('password', PasswordType::class, ['label' => 'Mot de passe', 'attr' => ['class' => 'form-control']])
            ->add('telephone', TelType::class, ['label' => 'Téléphone', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('addresse', TextType::class, ['label' => 'Adresse', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('profil', EntityType::class, [
                'label' => 'Profil',
                'class' => Profil::class,
                'choice_label' => 'type',
                'attr' => ['class' => 'form-control'],
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}