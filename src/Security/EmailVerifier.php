<?php

namespace App\Security;

use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use Doctrine\ORM\EntityManagerInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {}

    public function sendEmailConfirmation(User $user): void
    {
        /** @var VerifyEmailSignatureComponents $signature */
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
                <a href='$url'>
                    Vérifier mon email
                </a>
            ");

        $this->mailer->send($email);
    }

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

    public function generateResetSignature(User $user): \SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents
    {
        return $this->verifyEmailHelper->generateSignature(
            'app_reset_password_reset',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );
    }

    public function validateResetToken(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string) $user->getId(),
            $user->getEmail()
        );
    }

    public function sendEmail(\Symfony\Component\Mime\Email $email): void
    {
        $this->mailer->send($email);
    }
}