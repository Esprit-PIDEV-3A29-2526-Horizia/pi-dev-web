<?php
// src/Command/CancelExpiredReservationsCommand.php

namespace App\Command;

use App\Entity\Reservationlog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CancelExpiredReservationsCommand extends Command
{
    protected static $defaultName = 'app:cancel-expired-reservations';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription('Annule les réservations en ligne en attente de paiement depuis plus de 24h');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (new \DateTime())->modify('-24 hours');
        $qb = $this->em->createQueryBuilder();
        $expired = $qb->select('r')
            ->from(Reservationlog::class, 'r')
            ->where('r.status = :status')
            ->andWhere('r.modalites = :modalite')
            ->andWhere('r.createdAt < :limit')
            ->setParameter('status', 'en_attente')
            ->setParameter('modalite', 'En ligne')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->getResult();

        foreach ($expired as $reservation) {
            $reservation->setStatus('expirée');
            $output->writeln("Réservation #{$reservation->getIdreslog()} expirée (délai dépassé)");
        }
        $this->em->flush();
        $output->writeln('Terminé. ' . count($expired) . ' réservation(s) expirée(s).');
        return Command::SUCCESS;
    }
}