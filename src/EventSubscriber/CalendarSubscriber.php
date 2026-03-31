<?php

namespace App\EventSubscriber;

use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;

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
            ->getQuery()
            ->getResult();

        foreach ($voyages as $voyage) {
            $event = new Event(
                $voyage->getTitre() ?: 'Voyage',
                $voyage->getDateDepart(),
                $voyage->getDateRetour()
            );

            $event->setOptions([
                'backgroundColor' => '#3D94CA',
                'borderColor' => '#23779C',
                'textColor' => '#ffffff',
            ]);

            $event = new Event(
                ($voyage->getTitre() ?: 'Voyage') . ' - ' . $voyage->getDestination(),
                $voyage->getDateDepart(),
                $voyage->getDateRetour()
            );

            $event->addOption('url', '/admin/voyage/edit/' . $voyage->getId());

            $calendar->addEvent($event);
        }
    }
}