<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auteur', TextType::class, [
                'label' => 'Votre nom',
                'attr' => ['placeholder' => 'Votre nom', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => ['rows' => 4, 'placeholder' => 'Votre commentaire...', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le commentaire ne peut pas être vide.']),
                    new Length(['min' => 2, 'max' => 500])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}