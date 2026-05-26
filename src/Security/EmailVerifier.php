<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

use App\Entity\User;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * ENVOI EMAIL DE CONFIRMATION
     */
    public function sendEmailConfirmation(User $user): void
    {
        $signature = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $url = $signature->getSignedUrl();

        $email = (new Email())
            ->from('no-reply@test.com')
            ->to($user->getEmail())
            ->subject('Confirme ton email')
            ->html("
                <h1>Confirme ton compte</h1>
                <p>Clique ici :</p>
                <a href='{$url}'>Vérifier mon email</a>
            ");

        $this->mailer->send($email);
    }

    /**
     * VALIDATION EMAIL
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string) $user->getId(),
            $user->getEmail()
        );

        $user->setIsVerified(true);
        $this->entityManager->flush();
    }

    /**
     * RESET PASSWORD - génération lien
     */
    public function generateResetSignature(User $user)
    {
        return $this->verifyEmailHelper->generateSignature(
            'app_reset_password_reset',
            (string) $user->getId(),
            $user->getEmail()
        );
    }

    /**
     * VALIDATION RESET PASSWORD TOKEN
     */
    public function validateResetToken(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string) $user->getId(),
            $user->getEmail()
        );
    }

    /**
     * ENVOI EMAIL GÉNÉRIQUE
     */
    public function sendEmail(Email $email): void
    {
        $this->mailer->send($email);
    }
}