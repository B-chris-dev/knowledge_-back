<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateLessonInput;
use App\Entity\Lesson;
use App\Repository\CursusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CreateLessonProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private CursusRepository $cursusRepository
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Lesson {

        /** @var CreateLessonInput $data */

        $lesson = new Lesson();

        // ------------------------
        // DONNÉES
        // ------------------------

        $lesson->setName($data->name);

        $lesson->setPrice($data->price);

        $lesson->setText($data->text);

        $lesson->setVideo($data->video);

        // ------------------------
        // CURSUS
        // ------------------------

        $cursus = $this->cursusRepository->find($data->cursus);

        if (!$cursus) {
            throw new \Exception('Cursus introuvable');
        }

        $lesson->setCursus($cursus);

        // ------------------------
        // USER CONNECTÉ
        // ------------------------

        $user = $this->security->getUser();

        $lesson->setCreatedBy($user);

        $lesson->setUpdatedBy($user);

        // ------------------------
        // SAVE
        // ------------------------

        $this->em->persist($lesson);

        $this->em->flush();

        return $lesson;
    }
}