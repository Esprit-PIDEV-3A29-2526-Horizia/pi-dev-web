<?php
// src/Command/SendExpirationReminderCommand.php

namespace App\Command;

use App\Entity\Reservationlog;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendExpirationReminderCommand extends Command
{
    protected static $defaultName = 'app:send-expiration-reminder';
    private EntityManagerInterface $em;
    private EmailService $emailService;

    public function __construct(EntityManagerInterface $em, EmailService $emailService)
    {
        parent::__construct();
        $this->em = $em;
        $this->emailService = $emailService;
    }

    protected function configure(): void
    {
        $this->setDescription('Envoie un rappel aux réservations en ligne qui expirent dans moins d\'1 heure');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();
        // Fenêtre des réservations créées il y a entre 23h et 24h (expiration dans l'heure)
        $minCreated = (clone $now)->modify('-24 hours');
        $maxCreated = (clone $now)->modify('-23 hours');

        $qb = $this->em->createQueryBuilder();
        $reservations = $qb->select('r')
            ->from(Reservationlog::class, 'r')
            ->where('r.status = :status')
            ->andWhere('r.modalites = :modalite')
            ->andWhere('r.createdAt BETWEEN :minCreated AND :maxCreated')
            ->setParameter('status', 'en_attente')
            ->setParameter('modalite', 'En ligne')
            ->setParameter('minCreated', $minCreated)
            ->setParameter('maxCreated', $maxCreated)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($reservations as $reservation) {
            $expiresAt = (clone $reservation->getCreatedAt())->modify('+24 hours');
            $remainingSeconds = $expiresAt->getTimestamp() - (new \DateTime())->getTimestamp();
            if ($remainingSeconds > 0 && $remainingSeconds <= 3600) {
                $this->emailService->sendPaymentReminderEmail($reservation, $remainingSeconds);
                $count++;
                $output->writeln("Rappel envoyé pour la réservation #{$reservation->getIdreslog()}");
            }
        }
        $output->writeln("$count rappel(s) envoyé(s).");
        return Command::SUCCESS;
    }
}