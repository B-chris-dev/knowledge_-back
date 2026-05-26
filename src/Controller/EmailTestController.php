<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class EmailTestController
{
    #[Route('/api/test-email', name: 'app_test_email', methods: ['POST'])]
    public function sendTestEmail(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $to = $request->request->get('to') ?? $request->query->get('to') ?? $data['to'] ?? null;

        if (!$to) {
            return new JsonResponse([
                'error' => 'Missing email destination parameter: to'
            ], 400);
        }

        $email = (new Email())
            ->from('no-reply@test.com')
            ->to($to)
            ->subject('Test d\'email Symfony')
            ->html('<p>Ceci est un test d\'envoi d\'email depuis Symfony.</p>');

        $mailer->send($email);

        return new JsonResponse([
            'message' => 'Email de test envoyé',
            'to' => $to,
        ]);
    }

    #[Route('/api/test-email-verify', name: 'app_test_email_verify', methods: ['GET', 'POST'])]
    public function sendVerifyEmail(Request $request, UserRepository $userRepository, EmailVerifier $emailVerifier): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = $request->get('email') ?? $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse([
                'error' => 'Missing email parameter: email',
                'content' => $request->getContent(),
                'query' => $request->query->all(),
                'request' => $request->request->all(),
            ], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'Utilisateur non trouvé pour cet email'
            ], 404);
        }

        try {
            $emailVerifier->sendEmailConfirmation($user);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Échec de l\'envoi du mail de vérification',
                'detail' => $e->getMessage(),
            ], 500);
        }

        return new JsonResponse([
            'message' => 'Email de vérification envoyé',
            'email' => $email,
        ]);
    }
}
