<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateCursusInput;
use App\Entity\Cursus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CreateCursusProcessor implements ProcessorInterface
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
    ): Cursus {

        /** @var CreateCursusInput $data */

        $cursus = new Cursus();

        $cursus->setName($data->name);

        $cursus->setPrice($data->price);

        $user = $this->security->getUser();

        $cursus->setCreatedBy($user);

        $cursus->setUpdatedBy($user);

        $this->em->persist($cursus);

        $this->em->flush();

        return $cursus;
    }
}