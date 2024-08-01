<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\TokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function registerUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer, TokenGenerator $tokenGenerator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPlainPassword());
            $user->setPassword($hashedPassword);
            $user->eraseCredentials();

            $token = $tokenGenerator->generateToken();
            $user->setVerificationToken($token);

            $entityManager->persist($user);
            $entityManager->flush();

            $email = (new Email())
                ->from('noreply@example.com')
                ->to($user->getEmail())
                ->subject('Please Verify Your Email')
                ->html($this->renderView('emails/verification.html.twig', [
                    'user' => $user,
                    'token' => $token
                ]));

            $mailer->send($email);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/notice', name: 'app_verify_notice')]
    public function verifyNotice(): Response
    {
        return new Response('Please check your email to verify your account.');
    }
}
