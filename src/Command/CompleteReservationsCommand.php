<?php
namespace App\Command;

use App\Entity\Reservationlog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteReservationsCommand extends Command
{
    protected static $defaultName = 'app:complete-reservations';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription('Passe les réservations terminées (date de départ passée) au statut "terminée"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();
        $qb = $this->em->createQueryBuilder();
        $reservations = $qb->select('r')
            ->from(Reservationlog::class, 'r')
            ->where('r.dateFin < :now')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('now', $now)
            ->setParameter('statuses', ['confirmée', 'en_attente'])
            ->getQuery()
            ->getResult();

        foreach ($reservations as $reservation) {
            $reservation->setStatus('terminée');
            $output->writeln('Réservation #' . $reservation->getIdreslog() . ' passée en "terminée"');
        }
        $this->em->flush();
        $output->writeln('Terminé. ' . count($reservations) . ' réservation(s) mise(s) à jour.');
        return Command::SUCCESS;
    }
}