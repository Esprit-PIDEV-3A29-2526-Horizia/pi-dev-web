<?php

namespace App\Form;

use App\Entity\Publication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, ['label' => 'Titre'])
            ->add('categorie', TextType::class, ['label' => 'Catégorie'])
            ->add('ville', TextType::class, ['required' => false, 'label' => 'Ville'])
            ->add('pays', TextType::class, ['required' => false, 'label' => 'Pays'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 6]])
            ->add('tags', TextType::class, ['required' => false, 'label' => 'Tags (séparés par des virgules)'])
            ->add('imageFile', FileType::class, ['mapped' => false, 'required' => false, 'label' => 'Photo'])
            ->add('pseudo', TextType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'Votre pseudo (pour vos publications)',
                'attr' => ['placeholder' => 'Ex: JeanDupont']
            ])
            ->add('imagePrompt', TextareaType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Décrivez l’image que vous souhaitez générer (optionnel)',
                'attr' => ['rows' => 2, 'placeholder' => 'Ex: un coucher de soleil sur une plage tropicale']
            ])
            // 👇 NOUVEAU CHAMP : sujet pour la génération automatique par IA
            ->add('sujet', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Sujet (ou mot-clé) pour la génération automatique',
                'attr' => ['placeholder' => 'Ex: Voyage à Dubaï, Randonnée dans les Alpes...']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
        ]);
    }
}