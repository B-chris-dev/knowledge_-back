<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\ValidPassword;

class RegisterInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[ValidPassword]
    public string $password;
}