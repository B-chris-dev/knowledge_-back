<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateThemeInput;
use App\Entity\Theme;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CreateThemeProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Theme {

        /** @var CreateThemeInput $data */

        $theme = new Theme();

        $theme->setName($data->name);

        $theme->setCreatedBy($this->security->getUser());

        $theme->setUpdatedBy($this->security->getUser());

        $this->em->persist($theme);
        $this->em->flush();

        return $theme;
    }
}