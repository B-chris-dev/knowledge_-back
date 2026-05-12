<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Validator\ValidPassword;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ResetPasswordController
{
    public function __construct(
        private UserRepository $userRepository,
        private EmailVerifier $emailVerifier,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    #[Route('/api/reset-password/request', name: 'app_reset_password_request', methods: ['POST'])]
    public function request(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email requis'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Pour sécurité, on ne révèle pas si l'email existe ou pas
            return new JsonResponse(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
        }

        // Générer un token de réinitialisation (utilisant le même système que verify-email)
        $this->sendResetEmail($user);

        return new JsonResponse(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
    }

    #[Route('/api/reset-password/reset/{id}', name: 'app_reset_password_reset', methods: ['POST'])]
    public function reset(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newPassword = $data['password'] ?? null;

        if (!$newPassword) {
            return new JsonResponse(['error' => 'Nouveau mot de passe requis'], 400);
        }

        // Valider le mot de passe
        $errors = $this->validator->validate($newPassword, new ValidPassword());
        if (count($errors) > 0) {
            return new JsonResponse(['error' => $errors[0]->getMessage()], 400);
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        try {
            // Valider le token (similaire à verify-email)
            $this->emailVerifier->validateResetToken($request, $user);

            // Changer le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Lien invalide ou expiré'], 400);
        }
    }

    private function sendResetEmail(User $user): void
    {
        $signature = $this->emailVerifier->generateResetSignature($user);

        $url = $signature->getSignedUrl();

        $email = (new \Symfony\Component\Mime\Email())
            ->from('no-reply@test.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html("
                <h1>Réinitialisation du mot de passe</h1>
                <a href='$url'>
                    Réinitialiser mon mot de passe
                </a>
            ");

        $this->emailVerifier->sendEmail($email);
    }
}