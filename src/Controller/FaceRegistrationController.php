<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaceRegistrationController extends AbstractController
{
    #[Route('/admin/register-face', name: 'admin_register_face')]
    public function registerPage(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('front/user/face_register.html.twig');
    }

    #[Route('/admin/register-face/save', name: 'admin_register_face_save', methods: ['POST'])]
    public function saveFace(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $imageBase64 = $request->request->get('image');
        if (!$imageBase64) {
            return $this->json(['success' => false, 'error' => 'Aucune image reçue'], 400);
        }

        // Créer le dossier uploads s'il n'existe pas
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filePath = $uploadDir . '/admin_face.jpg';
        file_put_contents($filePath, base64_decode($imageBase64));

        return $this->json(['success' => true]);
    }
}