<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Voyage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $placesRestantes = $options['places_restantes'];

        $builder
            ->add('dateReservation', DateTimeType::class, [
                'label' => 'Date réservation',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'EN_ATTENTE' => 'EN_ATTENTE',
                    'CONFIRMEE' => 'CONFIRMEE',
                    'ANNULEE' => 'ANNULEE',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('voyage', EntityType::class, [
                'class' => Voyage::class,
                'choice_label' => 'titre',
                'label' => 'Voyage',
                'placeholder' => 'Choisir un voyage',
                'attr' => ['class' => 'form-select']
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    $nom = method_exists($user, 'getNom') ? $user->getNom() : '';
                    $prenom = method_exists($user, 'getPrenom') ? $user->getPrenom() : '';
                    $fullName = trim($nom . ' ' . $prenom);

                    return $fullName !== '' ? $fullName : ('Utilisateur #' . $user->getId());
                },
                'label' => 'Client',
                'placeholder' => 'Choisir un utilisateur',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('nbrPersonnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => $placesRestantes,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir le nombre de personnes.'
                    ]),
                    new Assert\Positive([
                        'message' => 'Le nombre de personnes doit être supérieur à 0.'
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => $placesRestantes,
                        'message' => 'Le nombre de personnes ne peut pas dépasser les places disponibles.'
                    ]),
                ]
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $reservation = $event->getData();
            $form = $event->getForm();

            if (!$reservation) {
                return;
            }

            $voyage = $reservation->getVoyage();
            $nbrPersonnes = $reservation->getNbrPersonnes();

            if ($voyage && $nbrPersonnes && $nbrPersonnes > $voyage->getPlacesRestantes()) {
                $form->get('nbrPersonnes')->addError(
                    new FormError('Le nombre de personnes ne peut pas dépasser les places disponibles.')
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'places_restantes' => 9999,
        ]);

        $resolver->setAllowedTypes('places_restantes', 'int');
    }
}