<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\Cursus;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController
{
    // Controller responsible for creating Stripe checkout sessions
    // for lesson and cursus purchases.

    #[Route('/api/checkout/lesson/{id}', methods: ['POST'])]
    public function checkout(
        Lesson $lesson,
        Security $security
    ): JsonResponse {
        // Create a Stripe checkout session for a single lesson purchase.

        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = Session::create([
            'payment_method_types' => ['card'],

            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $lesson->getName(),
                    ],
                    'unit_amount' => (int) ($lesson->getPrice() * 100),
                ],
                'quantity' => 1,
            ]],

            'mode' => 'payment',

            'success_url' => 'http://localhost:3000/success',

            'cancel_url' => 'http://localhost:3000/cancel',

            'metadata' => array_filter([
                'user_id' => is_object($user) && method_exists($user, 'getId') ? $user->getId() : null,
                'user_identifier' => is_object($user) && method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (is_string($user) ? $user : null),
                'lesson_id' => $lesson->getId(),
            ]),
        ]);

        return new JsonResponse([
            'url' => $session->url
        ]);
    }

    #[Route('/api/checkout/cursus/{id}', methods: ['POST'])]
    public function checkoutCursus(
        Cursus $cursus,
        Security $security
    ): JsonResponse {
        // Create a Stripe checkout session for purchasing a full cursus.
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $cursus->getName(),
                    ],
                    'unit_amount' => (int) ($cursus->getPrice() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'http://localhost:3000/success',
            'cancel_url' => 'http://localhost:3000/cancel',
            'metadata' => array_filter([
                'user_id' => is_object($user) && method_exists($user, 'getId') ? $user->getId() : null,
                'user_identifier' => is_object($user) && method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (is_string($user) ? $user : null),
                'cursus_id' => $cursus->getId(),
            ]),
        ]);

        return new JsonResponse(['url' => $session->url]);
    }
}