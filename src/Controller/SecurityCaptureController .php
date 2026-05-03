<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class SecurityCaptureController extends AbstractController
{
    #[Route('/security-capture', name: 'app_security_capture')]
    public function capture(): Response
    {
        return $this->render('security/capture.html.twig');
    }

    #[Route('/security-capture/send', name: 'app_security_capture_send', methods: ['POST'])]
    public function sendCapture(Request $request, MailerInterface $mailer): Response
    {
        $imageBase64 = $request->request->get('image');
        if ($imageBase64) {
            $imageData = base64_decode((string) $imageBase64);
            $email = (new Email())
                ->from('no-reply@horozia.com')
                ->to('khalilbenlahmer@gmail.com')
                ->subject('Alerte sécurité : tentative d\'accès suspecte (avec capture)')
                ->html('<p>Une personne a tenté de se connecter avec le compte admin et a échoué 3 fois.</p>')
                ->attach($imageData, 'suspect.jpg', 'image/jpeg');
            $mailer->send($email);
        }
        return $this->redirectToRoute('app_login');
    }
}