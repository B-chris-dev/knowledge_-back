<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class VerifyEmailController
{
    // Controller endpoint that validates the user's verification link.
    #[Route('/api/verify-email/{id}', name: 'app_verify_email', methods: ['GET'])]
    public function verify(
        Request $request,
        UserRepository $userRepository,
        EmailVerifier $emailVerifier,
        int $id
    ): JsonResponse {

        // Load the user by the ID included in the verification URL.
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        try {
            $emailVerifier->handleEmailConfirmation($request, $user);

            return new JsonResponse([
                'message' => 'Email vérifié avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Lien invalide ou expiré'
            ], 400);
        }
    }
}
