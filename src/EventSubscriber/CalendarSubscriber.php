<?php

namespace App\EventSubscriber;

use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();

        $voyages = $this->entityManager->getRepository(Voyage::class)
            ->createQueryBuilder('v')
            ->where('v.dateDepart <= :end')
            ->andWhere('v.dateRetour >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('v.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($voyages as $voyage) {
            $title = ($voyage->getTitre() ?: 'Voyage') . ' - ' . ($voyage->getDestination() ?: '');

            $event = new Event(
                $title,
                $voyage->getDateDepart(),
                $voyage->getDateRetour()
            );

            $event->setOptions([
                'backgroundColor' => '#dc3545',
                'borderColor' => '#b02a37',
                'textColor' => '#ffffff',
            ]);

            $event->addOption('extendedProps', [
                'titre' => $voyage->getTitre(),
                'destination' => $voyage->getDestination(),
                'prix' => $voyage->getPrix(),
                'dateDepart' => $voyage->getDateDepart() ? $voyage->getDateDepart()->format('Y-m-d') : '',
                'dateRetour' => $voyage->getDateRetour() ? $voyage->getDateRetour()->format('Y-m-d') : '',
            ]);

            $calendar->addEvent($event);
        }
    }
}