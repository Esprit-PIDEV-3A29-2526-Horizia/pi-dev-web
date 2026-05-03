<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Events;
use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Service\LastFmService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
//use Symfony\Component\Routing\Attribute\Route;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Component\Routing\Annotation\Route;

class ParticipationController extends AbstractController
{
    // ══════════════════════════════════════════
    //  FRONT
    // ══════════════════════════════════════════

    #[Route('/front/event/{id}', name: 'app_front_event_show', methods: ['GET'])]
    public function eventShow(int $id, EntityManagerInterface $em, LastFmService $lastFmService): Response
    {
        
        $event = $em->getRepository(Events::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $artistInfo = null;
        if (stripos($event->getCategorie(), 'concert') !== false) {
            try {
                $artistName = str_ireplace([' Concert', ' Live', ' Show', ' Performance', ' concert', ' live', ' show', ' performance'], '', $event->getTitre());
                $artistName = trim($artistName);
                $artistInfo = $lastFmService->getArtistInfo($artistName);
            } catch (\Exception $e) {
                $artistInfo = null;
            }
        }

        return $this->render('front/event_show.html.twig', [
            'event' => $event,
            'artistInfo' => $artistInfo,
        ]);
    }

    #[Route('/front/event/{id}/register', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function register(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Events::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        if ($event->getPlaces_restantes() <= 0) {
            $this->addFlash('error', 'Désolé, cet événement est complet !');
            return $this->redirectToRoute('app_front_event_show', ['id' => $id]);
        }

        $participation = new Participation();
        $form = $this->createForm(ParticipationType::class, $participation);
        
        // Check if user is logged in
        $user = $this->getUser();
        if ($user instanceof User) {
            // Pre-fill form with user data
            $form->get('prenom')->setData($user->getPrenom());
            $form->get('nom')->setData($user->getNom());
            $form->get('email')->setData($user->getEmail());
            $form->get('telephone')->setData($user->getTelephone());
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nombrePlaces = $participation->getNombre_places();
            $montant = $nombrePlaces * floatval($event->getPrix());
            
            $userEmail = $form->get('email')->getData();
            $prenom = $form->get('prenom')->getData();
            $nom = $form->get('nom')->getData();
            $telephone = $form->get('telephone')->getData();

            $request->getSession()->set('pending_participation', [
                'event_id' => $event->getId_event(),
                'nombre_places' => $nombrePlaces,
                'montant_total' => $montant,
                'email' => $userEmail,
                'prenom' => $prenom,
                'nom' => $nom,
                'telephone' => $telephone
            ]);

            return $this->redirectToRoute('app_participation_payment_page', ['id' => $id]);
        }

        return $this->render('front/participation_new.html.twig', [
            'event' => $event,
            'form'  => $form->createView(),
        ]);
    }

    #[Route('/front/event/{id}/payment', name: 'app_participation_payment_page', methods: ['GET'])]
    public function paymentPage(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Events::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }
        
        $pendingData = $request->getSession()->get('pending_participation');
        
        if (!$pendingData || $pendingData['event_id'] != $id) {
            $this->addFlash('error', 'Données de réservation introuvables. Veuillez recommencer.');
            return $this->redirectToRoute('app_participation_new', ['id' => $id]);
        }
        
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        
        // Convert TND to cents (using EUR as base currency)
        $montantCentimes = intval($pendingData['montant_total'] * 100);
        
        $paymentIntent = PaymentIntent::create([
            'amount' => $montantCentimes,
            'currency' => 'eur',
            'metadata' => [
                'event_id' => (string) $event->getId_event(),
                'nombre_places' => (string) $pendingData['nombre_places'],
                'user_email' => (string) $pendingData['email']
            ]
        ]);
        
        return $this->render('front/payment.html.twig', [
            'event' => $event,
            'pending_data' => $pendingData,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
            'client_secret' => $paymentIntent->client_secret,
        ]);
    }
    
    #[Route('/front/event/payment/confirm', name: 'app_participation_confirm_payment', methods: ['POST'])]
    public function confirmPayment(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $data = json_decode($request->getContent(), true);
        $paymentIntentId = $data['payment_intent_id'] ?? null;
        
        if (!$paymentIntentId) {
            return $this->json(['success' => false, 'error' => 'No payment intent ID'], 400);
        }
        
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status === 'succeeded') {
                $pendingData = $request->getSession()->get('pending_participation');
                
                if (!$pendingData) {
                    return $this->json(['success' => false, 'error' => 'No pending data'], 400);
                }
                
                $event = $em->getRepository(Events::class)->find($pendingData['event_id']);
                
                if (!$event) {
                    return $this->json(['success' => false, 'error' => 'Event not found'], 404);
                }
                
                // Check available places again
                if ($event->getPlaces_restantes() < $pendingData['nombre_places']) {
                    return $this->json(['success' => false, 'error' => 'Plus de places disponibles'], 400);
                }
                
                $participation = new Participation();
                $participation->setId_event($event);
                $participation->setNombre_places($pendingData['nombre_places']);
                $participation->setMontant_total((string) $pendingData['montant_total']);
                $participation->setStatut('confirmée');
                $participation->setDate_participation(new \DateTime());
                //$participation->setEmail($pendingData['email']);
                
                $em->persist($participation);
                
                $newPlacesRestantes = $event->getPlaces_restantes() - $pendingData['nombre_places'];
                $event->setPlaces_restantes($newPlacesRestantes);
                
                $em->flush();
                
                $email = (new Email())
                    ->from('noreply@horozia.com')
                    ->to($pendingData['email'])
                    ->subject('Confirmation de paiement - ' . $event->getTitre())
                    ->html($this->renderView('emails/participation_confirmation.html.twig', [
                        'participation' => $participation,
                        'event' => $event,
                    ]));
                
                $mailer->send($email);
                
                $request->getSession()->remove('pending_participation');
                
                return $this->json(['success' => true]);
            } else {
                return $this->json(['success' => false, 'error' => 'Payment status: ' . $paymentIntent->status], 400);
            }
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
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