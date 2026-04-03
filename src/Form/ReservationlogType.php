<?php

namespace App\Form;

use App\Entity\Reservationlog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idlog')
            ->add('idc')
            ->add('date_debut')
            ->add('date_fin')
            ->add('montant')
            ->add('status')
            ->add('modalites')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationlog::class,
        ]);
    }
}
