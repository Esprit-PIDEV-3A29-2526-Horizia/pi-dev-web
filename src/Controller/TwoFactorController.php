<?php

namespace App\Controller;

use App\Service\CompreFaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa/verify', name: 'app_2fa_verify')]
    public function verifyPage(SessionInterface $session)
    {
        if (!$session->get('2fa_pending')) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('front/user/2fa_verify.html.twig');
    }
    
    #[Route('/test-2fa', name: 'test_2fa')]
public function test(): JsonResponse
{
    return $this->json(['message' => 'OK']);
}
#[Route('/test-2fa-check', name: 'test_2fa_check', methods: ['POST'])]
public function testCheck(Request $request): JsonResponse
{
    return $this->json(['message' => 'OK', 'image' => $request->request->get('image') ? 'received' : 'none']);
}
    #[Route('/2fa/verify/check', name: 'app_2fa_verify_check', methods: ['POST'])]
   public function verifyCheck(Request $request, SessionInterface $session): JsonResponse
{
    if (!$session->get('2fa_pending')) {
        return $this->json(['success' => false, 'message' => 'Requête invalide'], 400);
    }

    // Simuler la vérification réussie
    $session->remove('2fa_pending');
    return $this->json(['success' => true]);
}
    #[Route('/test-2fa', name: 'test_2fa', methods: ['POST'])]
public function test2fa(Request $request): JsonResponse
{
    $image = $request->request->get('image');
    return $this->json(['success' => true, 'message' => 'Test OK', 'image_received' => !empty($image)]);
}
}