<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/change-locale/{locale}', name: 'change_locale')]
    public function changeLocale(Request $request, string $locale): Response
    {
        if (!in_array($locale, ['fr', 'en', 'ar'])) {
            $locale = 'fr';
        }
        
        $request->getSession()->set('_locale', $locale);
        
        // 🔥 CETTE LIGNE EST CRUCIALE
        $request->setLocale($locale);
        
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('app_front_home');
    }
}