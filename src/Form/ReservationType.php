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
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'EN_ATTENTE' => 'EN_ATTENTE',
                    'CONFIRMEE' => 'CONFIRMEE',
                    'ANNULEE' => 'ANNULEE',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('voyage', EntityType::class, [
                'class' => Voyage::class,
                'choice_label' => 'titre',
                'label' => 'Voyage',
                'placeholder' => 'Choisir un voyage',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user): string {
                    $nom = (string) $user->getNom();
                    $prenom = (string) $user->getPrenom();
                    $fullName = trim($nom . ' ' . $prenom);

                    return $fullName !== '' ? $fullName : ('Utilisateur #' . $user->getId());
                },
                'label' => 'Client',
                'placeholder' => 'Choisir un utilisateur',
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('nbAdultes', IntegerType::class, [
                'label' => 'Nombre d’adultes',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => $placesRestantes,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir le nombre d’adultes.',
                    ]),
                    new Assert\PositiveOrZero([
                        'message' => 'Le nombre d’adultes doit être positif ou nul.',
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => $placesRestantes,
                        'message' => 'Le nombre d’adultes ne peut pas dépasser les places disponibles.',
                    ]),
                ],
            ])
            ->add('nbEnfants', IntegerType::class, [
                'label' => 'Nombre d’enfants',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => $placesRestantes,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir le nombre d’enfants.',
                    ]),
                    new Assert\PositiveOrZero([
                        'message' => 'Le nombre d’enfants doit être positif ou nul.',
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => $placesRestantes,
                        'message' => 'Le nombre d’enfants ne peut pas dépasser les places disponibles.',
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $reservation = $event->getData();
            $form = $event->getForm();

            if (!$reservation instanceof Reservation) {
                return;
            }

            $voyage = $reservation->getVoyage();
            $nbrPersonnes = $reservation->getNbrPersonnes();

            if ($voyage !== null && $nbrPersonnes !== null && $nbrPersonnes > $voyage->getPlacesRestantes()) {
                if ($form->has('nbAdultes')) {
                    $form->get('nbAdultes')->addError(
                        new FormError('Le nombre total de personnes ne peut pas dépasser les places disponibles.')
                    );
                }
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