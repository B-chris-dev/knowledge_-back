<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;

class UserChecker implements UserCheckerInterface
{
    // Prevent login if the user has not verified their email address.
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException('Votre email n\'est pas vérifié. Veuillez vérifier votre email avant de vous connecter.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No additional post-authentication checks are required.
    }
}