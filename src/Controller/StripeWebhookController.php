<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Repository\UserRepository;
use App\Repository\LessonRepository;
use App\Repository\CursusRepository;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController
{
    #[Route('/api/stripe/webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        UserRepository $userRepository,
        LessonRepository $lessonRepository,
        CursusRepository $cursusRepository,
        PurchaseRepository $purchaseRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): Response {
        try {
            $payload = $request->getContent();
            $sigHeader = $request->headers->get('stripe-signature');

            if (!$sigHeader) {
                $logger->error('Stripe webhook: missing signature header');
                return new Response('Missing signature header', 400);
            }

            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );

            $logger->info('Stripe webhook received', ['type' => $event->type]);

            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;

                $userId = $session->metadata->user_id ?? null;
                $lessonId = $session->metadata->lesson_id ?? null;
                $cursusId = $session->metadata->cursus_id ?? null;

                $user = null;
                if ($userId) {
                    $user = $userRepository->find($userId);
                } else {
                    $userIdentifier = $session->metadata->user_identifier ?? null;
                    if ($userIdentifier) {
                        $user = $userRepository->findOneBy(['email' => $userIdentifier]);
                    }
                }

                if (!$user) {
                    $logger->error('Stripe webhook: user not found', ['user_id' => $userId ?? null, 'user_identifier' => $session->metadata->user_identifier ?? null]);
                    return new Response('User not found', 404);
                }

                // Single lesson purchase
                if ($lessonId) {
                    $lesson = $lessonRepository->find($lessonId);
                    if (!$lesson) {
                        $logger->error('Stripe webhook: lesson not found', ['lesson_id' => $lessonId]);
                        return new Response('Lesson not found', 404);
                    }

                    // avoid duplicate
                    $existing = $purchaseRepository->findOneBy(['user' => $user, 'lesson' => $lesson, 'status' => 'paid']);
                    if (!$existing) {
                        $purchase = new Purchase();
                        $purchase->setUser($user);
                        $purchase->setLesson($lesson);
                        $purchase->setStatus('paid');
                        $purchase->setStripeSessionId($session->id);
                        $purchase->setCreatedAt(new \DateTimeImmutable());

                        $em->persist($purchase);
                    }

                    $logger->info('Purchase processed for lesson', ['lesson_id' => $lessonId, 'session' => $session->id]);
                }

                // Cursus purchase: grant access to all lessons
                if ($cursusId) {
                    $cursus = $cursusRepository->find($cursusId);
                    if (!$cursus) {
                        $logger->error('Stripe webhook: cursus not found', ['cursus_id' => $cursusId]);
                        return new Response('Cursus not found', 404);
                    }

                    foreach ($cursus->getLessons() as $lesson) {
                        $existing = $purchaseRepository->findOneBy(['user' => $user, 'lesson' => $lesson, 'status' => 'paid']);
                        if (!$existing) {
                            $purchase = new Purchase();
                            $purchase->setUser($user);
                            $purchase->setLesson($lesson);
                            $purchase->setStatus('paid');
                            $purchase->setStripeSessionId($session->id);
                            $purchase->setCreatedAt(new \DateTimeImmutable());

                            $em->persist($purchase);
                        }
                    }

                    $logger->info('Purchase processed for cursus', ['cursus_id' => $cursusId, 'session' => $session->id]);
                }

                $em->flush();
            }

            return new Response('ok', 200);
        } catch (SignatureVerificationException $e) {
            $logger->error('Stripe webhook: invalid signature', ['error' => $e->getMessage()]);
            return new Response('Invalid signature', 403);
        } catch (\Exception $e) {
            $logger->error('Stripe webhook error', ['error' => $e->getMessage()]);
            return new Response('Internal error', 500);
        }
    }
}