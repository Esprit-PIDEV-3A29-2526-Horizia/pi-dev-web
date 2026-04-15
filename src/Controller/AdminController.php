<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Entity\Reservationlog;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    // Démarrer la session si ce n'est pas déjà fait
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $em): Response
    {
        $pendingCancelRequests = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.idreslog)')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/index.html.twig', [
            'pendingCancelRequests' => $pendingCancelRequests,
        ]);
    }

    #[Route('/admin/reservations/cancel-requests', name: 'admin_cancel_requests')]
    public function cancelRequests(EntityManagerInterface $em): Response
    {
        $requests = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $pendingCancelRequests = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.idreslog)')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/cancel_requests.html.twig', [
            'requests' => $requests,
            'pendingCancelRequests' => $pendingCancelRequests,
        ]);
    }

    #[Route('/admin/reservation/approve-cancel/{id}', name: 'admin_approve_cancel', methods: ['POST'])]
    public function approveCancel(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getStatus() !== 'demande_annulation') {
            return $this->json(['error' => 'Réservation non trouvée ou pas en demande d\'annulation'], 404);
        }

        $reservation->setStatus('annulée');
        $em->flush();

        $emailService->sendCancellationApprovedEmail($reservation->getUser()->getEmail(), $reservation);
        $this->addFlash('success', '✅ Annulation approuvée pour la réservation #' . $reservation->getIdreslog());

        return $this->json(['success' => true, 'message' => 'Annulation confirmée.']);
    }

    #[Route('/admin/reservation/reject-cancel/{id}', name: 'admin_reject_cancel', methods: ['POST'])]
    public function rejectCancel(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getStatus() !== 'demande_annulation') {
            return $this->json(['error' => 'Réservation non trouvée ou pas en demande d\'annulation'], 404);
        }

        $reservation->setStatus('confirmée');
        $em->flush();

        $emailService->sendCancellationRejectedEmail($reservation->getUser()->getEmail(), $reservation);
        $this->addFlash('warning', '⚠️ Demande d\'annulation rejetée pour la réservation #' . $reservation->getIdreslog());

        return $this->json(['success' => true, 'message' => 'Demande rejetée.']);
    }
}