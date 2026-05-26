<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateCursusInput;
use App\Entity\Cursus;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CreateCursusProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private ThemeRepository $themeRepository
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Cursus {

        /** @var CreateCursusInput $data */

      
        $user = $this->security->getUser();

        if (!$user) {
            throw new \RuntimeException('Utilisateur non connecté');
        }

        $themeId = basename($data->theme);

$theme = $this->themeRepository->find($themeId);

        if (!$theme) {
            throw new \RuntimeException('Thème introuvable');
        }

        $cursus = new Cursus();

        $cursus->setName($data->name);

        $cursus->setPrice((string) $data->price);

        $cursus->setTheme($theme);

        $cursus->setCreatedBy($user);

        $cursus->setUpdatedBy($user);

        $this->em->persist($cursus);

        $this->em->flush();

        return $cursus;
    }
}