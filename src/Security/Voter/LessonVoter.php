<?php

namespace App\Security\Voter;

use App\Entity\Lesson;
use App\Entity\User;
use App\Repository\PurchaseRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LessonVoter extends Voter
{
    private const VIEW = 'VIEW';

    public function __construct(
        private PurchaseRepository $purchaseRepository
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Lesson;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($attribute === self::VIEW) {
            return $this->canView($subject, $user);
        }

        return false;
    }

    private function canView(Lesson $lesson, User $user): bool
    {
        // Check whether the user has a paid purchase record for this lesson.
        $purchase = $this->purchaseRepository->findOneBy([
            'user' => $user,
            'lesson' => $lesson,
            'status' => 'paid'
        ]);

        return $purchase !== null;
    }
}
