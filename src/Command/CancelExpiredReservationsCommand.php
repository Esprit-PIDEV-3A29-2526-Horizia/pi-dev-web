<?php
namespace App\Command;

use App\Entity\Reservationlog;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CancelExpiredReservationsCommand extends Command
{
    protected static $defaultName = 'app:cancel-expired-reservations';
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
        $this->setDescription('Annule les réservations en attente de paiement en ligne depuis plus de 24h');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (new \DateTime())->modify('-24 hours');
        $expired = $this->em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.modalites = :modalite')
            ->andWhere('r.createdAt < :limit')
            ->setParameter('status', 'en_attente')
            ->setParameter('modalite', 'En ligne')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->getResult();

        foreach ($expired as $reservation) {
            $reservation->setStatus('annulée');
            $this->emailService->sendCancellationEmail(
                $reservation->getUser()->getEmail(),
                $reservation,
                'Paiement non finalisé dans les 24h'
            );
            $output->writeln('Réservation #' . $reservation->getIdreslog() . ' annulée (délai dépassé)');
        }
        $this->em->flush();
        $output->writeln('Annulation terminée. ' . count($expired) . ' réservation(s) annulée(s).');
        return Command::SUCCESS;
    }
}