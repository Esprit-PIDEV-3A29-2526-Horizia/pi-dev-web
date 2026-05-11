<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Profil;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/connect/google/check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if (!$user) {
    $user = new User();
    $fullName = $googleUser->getName(); // ex: "John Doe"
    $parts = explode(' ', $fullName, 2);
    $prenom = $parts[0];
    $nom = $parts[1] ?? '';
    
    $user->setEmail($email);
    $user->setPrenom($prenom);
    $user->setNom($nom);
    
    // Attribuer un profil par défaut (ex: CLIENT, id=5)
    $profil = $this->entityManager->getRepository(Profil::class)->find(5);
    if (!$profil) {
        // Créer le profil CLIENT s'il n'existe pas
        $profil = new Profil();
        $profil->setType('CLIENT');
        $profil->setStatut('actif');
        $this->entityManager->persist($profil);
    }
    $user->setProfil($profil);
    
    $this->entityManager->persist($user);
    $this->entityManager->flush();
}
                return $user;
            })
        );
    }
public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    $user = $token->getUser();
    
    // Si l'utilisateur est l'administrateur (email spécifique ou rôle ADMIN)
    if ($user instanceof User && ($user->getEmail() === 'admin@admin.com' || in_array('ROLE_ADMIN', $user->getRoles()))) {
        return new RedirectResponse($this->router->generate('app_admin'));
    }
    
    // Pour tous les autres (clients), redirection vers l'accueil
    return new RedirectResponse($this->router->generate('app_front_home'));
}
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
    /**
     * @return array<int, string>
     */
    protected function getScopes(): array
{
    return ['email', 'profile']; // vos scopes
}
}