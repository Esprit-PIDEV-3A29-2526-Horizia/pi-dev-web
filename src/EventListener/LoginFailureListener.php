<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginFailureListener implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private RouterInterface $router;

    public function __construct(RequestStack $requestStack, RouterInterface $router)
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginFailureEvent::class => 'onLoginFailure'];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $username = $request->request->get('_username');

        if ($username !== 'admin@admin.com') {
            return;
        }

        $session = $this->requestStack->getSession();
        $failCount = $session->get('login_fail_count', 0);
        $failCount++;
        $session->set('login_fail_count', $failCount);

        if ($failCount >= 3) {
            // Stocker en session qu'il faut capturer le visage
            $session->set('capture_face_required', true);
            $session->remove('login_fail_count'); // réinitialiser le compteur
            // Rediriger vers la page de capture automatique
            $event->setResponse(new RedirectResponse($this->router->generate('app_capture_face')));
        }
    }
}