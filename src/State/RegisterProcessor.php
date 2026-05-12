<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Dto\RegisterInput;
use App\Security\EmailVerifier;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerifier $emailVerifier
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

    //hash password
    $user->setPassword(
        $this->passwordHasher->hashPassword($user, $data->password)
    );

    $user->setRoles(['ROLE_USER']);
    $user->setIsVerified(false);

    //Persist user
    $user = $this->persistProcessor->process(
        $user,
        $operation,
        $uriVariables,
        $context
    );

    //Send confirmation email
    $this->emailVerifier->sendEmailConfirmation($user);

    
    return $user;
}
}