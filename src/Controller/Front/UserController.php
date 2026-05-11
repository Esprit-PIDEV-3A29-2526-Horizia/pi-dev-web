<?php

namespace App\Controller\Front;
use App\Entity\PasswordReset;
use App\Entity\User;
use App\Entity\Profil;
use App\Form\ForgotPasswordType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('front/user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            $prenom = $request->request->get('firstname');
            $nom = $request->request->get('lastname');
            $email = $request->request->get('email');
            $plainPassword = $request->request->get('password');
            $telephone = $request->request->get('telephone');
            $addresse = $request->request->get('addresse');

            // Check if user already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_register');
            }

            // Get CLIENT profil (id = 5 from your database)
            $profil = $entityManager->getRepository(Profil::class)->find(5);
            if (!$profil) {
                // Create CLIENT profil if it doesn't exist
                $profil = new Profil();
                $profil->setType('CLIENT');
                $profil->setStatut('actif');
                $entityManager->persist($profil);
                $entityManager->flush();
            }

            // Create new user
            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setTelephone($telephone);
            $user->setAddresse($addresse);
            $user->setProfil($profil);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/user/register.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide, Symfony gère le logout.');
    }

#[Route('/after-login', name: 'app_after_login_redirect')]
public function afterLoginRedirect(Request $request): Response
{
    $user = $this->getUser();
    if (!$user instanceof User) {
        return $this->redirectToRoute('app_login');
    }

    // Vérifier si l'utilisateur est admin
    $isAdmin = ($user->getEmail() === 'admin@admin.com') || in_array('ROLE_ADMIN', $user->getRoles());

    if ($isAdmin) {
        return $this->redirectToRoute('app_admin');
    }

    // Client : redirection vers l'accueil
    return $this->redirectToRoute('app_front_home');
}

/**
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 */
#[Route('/connect/google', name: 'connect_google_start')]
public function connectGoogle(ClientRegistry $clientRegistry): Response
{
    /** @var \KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface $client */
    $client = $clientRegistry->getClient('google');
    return $client->redirect([], []);
}

#[Route('/connect/google/check', name: 'google_connect_check')]
public function connectGoogleCheck(): Response
{
    // Cette route ne sera jamais exécutée car l'authenticator intercepte l'appel.
    // Elle doit juste exister pour que la redirection Google soit valide.
    return $this->redirectToRoute('app_front_home');
}

#[Route('/forgot-password', name: 'app_forgot_password')]
public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer, SessionInterface $session): Response
{
    if ($request->isMethod('POST')) {
        $email = $request->request->get('email');
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            // Supprimer les anciens codes non utilisés pour cet email
            $oldTokens = $em->getRepository(PasswordReset::class)->findBy(['email' => $email, 'used' => false]);
            foreach ($oldTokens as $token) {
                $em->remove($token);
            }

            // Générer un code numérique à 6 chiffres
            $code = sprintf("%06d", random_int(0, 999999));

            $reset = new PasswordReset();
            $reset->setEmail($email);
            $reset->setCode($code); // stocke le code numérique
            $reset->setExpires_at(new \DateTime('+1 hour'));
            $reset->setCreated_at(new \DateTime());
            $reset->setUsed(false);

            $em->persist($reset);
            $em->flush();

            // Stocker l'email en session pour la suite
            $session->set('reset_email', $email);

            // Envoyer l'email contenant le code (pas de lien)
            $emailMessage = (new Email())
                ->from('no-reply@horozia.com')
                ->to($email)
                ->subject('Code de réinitialisation')
                ->html("
                    <h2>Bonjour,</h2>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                    <p>Votre code de vérification est : <strong>{$code}</strong></p>
                    <p>Ce code est valable 1 heure.</p>
                    <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
                ");

            $mailer->send($emailMessage);
        }

        // Message générique de sécurité
        $this->addFlash('success', 'Si cet email existe, vous allez recevoir un code de vérification.');
        return $this->redirectToRoute('app_verify_code');
    }

    return $this->render('front/user/forgot_password.html.twig');
}
#[Route('/verify-code', name: 'app_verify_code')]
public function verifyCode(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
{
    $email = $session->get('reset_email');
    if (!$email) {
        return $this->redirectToRoute('app_forgot_password');
    }

    if ($request->isMethod('POST')) {
        $submittedCode = $request->request->get('code');
        $reset = $em->getRepository(PasswordReset::class)->findOneBy([
            'email' => $email,
            'code' => $submittedCode,
            'used' => false,
        ]);

        if ($reset && $reset->getExpires_at() > new \DateTime()) {
            // Code valide : on le marque comme utilisé immédiatement ou on le garde pour l'étape suivante
            // Ici on le garde en session pour l'étape du nouveau mot de passe
            $session->set('reset_code', $submittedCode);
            return $this->redirectToRoute('app_reset_password_form');
        } else {
            $this->addFlash('error', 'Code invalide ou expiré.');
        }
    }

    return $this->render('front/user/verify_code.html.twig', ['email' => $email]);
}
#[Route('/reset-password-form', name: 'app_reset_password_form')]
public function resetPasswordForm(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, SessionInterface $session): Response
{
    $code = $session->get('reset_code');
    $email = $session->get('reset_email');

    if (!$code || !$email) {
        $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
        return $this->redirectToRoute('app_forgot_password');
    }

    $reset = $em->getRepository(PasswordReset::class)->findOneBy([
        'email' => $email,
        'code' => $code,
        'used' => false,
    ]);

    if (!$reset || $reset->getExpires_at() < new \DateTime()) {
        $this->addFlash('error', 'Code invalide ou expiré.');
        return $this->redirectToRoute('app_forgot_password');
    }

    if ($request->isMethod('POST')) {
        $newPassword = $request->request->get('password');
        $confirm = $request->request->get('confirm_password');

        if ($newPassword !== $confirm) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
        } elseif (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
        } else {
            // Récupérer l'utilisateur
            $user = $em->getRepository(User::class)->findOneBy(['email' => $reset->getEmail()]);
            if (!$user) {
                $this->addFlash('error', 'Utilisateur introuvable.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Hacher le nouveau mot de passe
            $hashedPassword = $hasher->hashPassword($user, $newPassword);

            // Mise à jour directe en base (contourne Doctrine)
            $conn = $em->getConnection();
            $conn->executeStatement('UPDATE user SET password = :password WHERE email = :email', [
                'password' => $hashedPassword,
                'email'    => $reset->getEmail()
            ]);

            // Marquer le code comme utilisé
            $reset->setUsed(true);
            $em->persist($reset);
            $em->flush();

            // Nettoyer la session
            $session->remove('reset_code');
            $session->remove('reset_email');

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Connectez-vous.');
            return $this->redirectToRoute('app_login');
        }
    }

    return $this->render('front/user/reset_password_form.html.twig');
}
}