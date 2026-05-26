<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Dto\RegisterInput;
use App\Security\EmailVerifier;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerifier $emailVerifier,
        private LoggerInterface $logger
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ) {
        if (!$data instanceof RegisterInput) {
            return null;
        }

        $user = new User();
        $user->setEmail($data->email);

        // Hash the password before persisting the new user.
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data->password)
        );

        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);

        // Persist the user through the decorated processor.
        $user = $this->persistProcessor->process(
            $user,
            $operation,
            $uriVariables,
            $context
        );

        // Send the email verification message after registration.
        try {
            $this->emailVerifier->sendEmailConfirmation($user);
        } catch (\Throwable $e) {
            $this->logger->error('Email verification failed: ' . $e->getMessage(), [
                'exception' => $e,
                'email' => $user->getEmail(),
            ]);
        }

        return $user;
    }
}
