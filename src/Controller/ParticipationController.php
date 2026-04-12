<?php

namespace App\Controller;

use App\Entity\Events;
use App\Entity\Participation;
use App\Form\ParticipationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ParticipationController extends AbstractController
{
    // ══════════════════════════════════════════
    //  FRONT
    // ══════════════════════════════════════════

    #[Route('/front/event/{id}', name: 'app_front_event_show', methods: ['GET'])]
    public function eventShow(int $id, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Events::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }
        return $this->render('front/event_show.html.twig', ['event' => $event]);
    }

    #[Route('/front/event/{id}/register', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function register(int $id, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $event = $em->getRepository(Events::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $participation = new Participation();
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nombrePlaces = $participation->getNombre_places();
            $montant      = $nombrePlaces * floatval($event->getPrix());

            $userEmail = $form->get('email')->getData();

            $participation->setId_event($event);
            $participation->setMontant_total((string) $montant);
            $participation->setStatut('confirmée');
            $participation->setDate_participation(new \DateTime());

            $event->setPlaces_restantes($event->getPlaces_restantes() - $nombrePlaces);

            $em->persist($participation);
            $em->flush();

            //email
            $email = (new Email())
                ->from('noreply@horozia.com')
                ->to($userEmail)  // ← Use email from form
                ->subject('Confirmation de participation - ' . $event->getTitre())
                ->html($this->renderView('emails/participation_confirmation.html.twig', [
                    'participation' => $participation,
                    'event' => $event,
                    'userEmail' => $userEmail,
                ]));

            $mailer->send($email);

            $this->addFlash('success', 'Votre inscription a été enregistrée avec succès !');
            return $this->redirectToRoute('app_front_mes_participations');
        }

        return $this->render('front/participation_new.html.twig', [
            'event' => $event,
            'form'  => $form->createView(),
        ]);
    }

    #[Route('/front/mes-participations', name: 'app_front_mes_participations', methods: ['GET'])]
    public function mesParticipations(EntityManagerInterface $em): Response
    {
        $participations = $em->getRepository(Participation::class)
            ->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.date_participation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('front/mes_participations.html.twig', [
            'participations' => $participations,
        ]);
    }

    // ══════════════════════════════════════════
    //  ADMIN
    // ══════════════════════════════════════════

    #[Route('/admin/participation', name: 'app_participation_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $participations = $em->getRepository(Participation::class)
            ->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.date_participation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/participation/index.html.twig', [
            'participations' => $participations,
        ]);
    }

    #[Route('/admin/participation/delete/{id}', name: 'app_participation_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $participation = $em->getRepository(Participation::class)->find($id);
        if (!$participation) {
            throw $this->createNotFoundException('Participation introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $participation->getId_participation(), $request->request->get('_token'))) {
            $event = $participation->getId_event();
            $event->setPlaces_restantes($event->getPlaces_restantes() + $participation->getNombre_places());
            $em->remove($participation);
            $em->flush();
            $this->addFlash('success', 'Participation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_participation_index');
    }
}