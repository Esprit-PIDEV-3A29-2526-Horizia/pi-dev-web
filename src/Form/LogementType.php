<?php

namespace App\Form;

use App\Entity\Logement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LogementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
->add('type', ChoiceType::class, [
            'label' => 'Type de logement',
            'choices' => [
                'Appartement' => 'Appartement',
                'Maison' => 'Maison',
                'Villa' => 'Villa',
                'Hôtel' => 'Hôtel',
            ],
            'placeholder' => 'Sélectionnez un type',
            'required' => true,
        ])
            ->add('nom')
            ->add('image')
            ->add('adresse')
            ->add('capacite')
            ->add('equipement')
            ->add('tarif_nuit')
            ->add('disponibilite')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Logement::class,
        'csrf_protection' => false, // À enlever après test
    ]);
}
}
