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
    // Processor that creates a lesson linked to a cursus and stores the author.
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

        // Set the lesson details from the input payload.

        $lesson->setName($data->name);

        $lesson->setPrice($data->price);

        $lesson->setText($data->text);

        $lesson->setVideo($data->video);

        // Link the lesson to the requested cursus.
        $cursus = $this->cursusRepository->find($data->cursus);

        if (!$cursus) {
            throw new \Exception('Cursus introuvable');
        }

        $lesson->setCursus($cursus);

        // Assign the current authenticated user as creator and updater.
        $user = $this->security->getUser();

        $lesson->setCreatedBy($user);
        $lesson->setUpdatedBy($user);

        // Save the lesson to the database.

        $this->em->persist($lesson);

        $this->em->flush();

        return $lesson;
    }
}