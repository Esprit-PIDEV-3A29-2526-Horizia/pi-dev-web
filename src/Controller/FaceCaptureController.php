<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class FaceCaptureController extends AbstractController
{
    #[Route('/capture-face', name: 'app_capture_face')]
    public function capturePage(SessionInterface $session): Response
    {
        if (!$session->get('capture_face_required')) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('front/security/capture_face.html.twig');
    }

    #[Route('/capture-face/send', name: 'app_capture_face_send', methods: ['POST'])]
    public function sendCapturedFace(Request $request, SessionInterface $session, MailerInterface $mailer): JsonResponse
    {
        if (!$session->get('capture_face_required')) {
            return $this->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $imageBase64 = $request->request->get('image');
        if ($imageBase64) {
            $imageData = base64_decode($imageBase64);
            $email = (new Email())
                ->from('no-reply@horozia.com')
                ->to('khalilbenlahmer@gmail.com')
                ->subject('Alerte sécurité : tentative d\'accès frauduleux avec photo')
                ->html('<p>Quelqu\'un a tenté de se connecter avec le compte admin après 3 échecs de mot de passe. Voici sa photo :</p>')
                ->attach($imageData, 'tentative_admin_face.jpg', 'image/jpeg');
            $mailer->send($email);
        } else {
            $email = (new Email())
                ->from('no-reply@horozia.com')
                ->to('khalilbenlahmer@gmail.com')
                ->subject('Alerte sécurité : tentative d\'accès frauduleux')
                ->html('<p>3 échecs de mot de passe sur le compte admin, mais impossible de capturer la photo (webcam non accessible).</p>');
            $mailer->send($email);
        }

        $session->remove('capture_face_required');
        return $this->json(['success' => true]);
    }
}