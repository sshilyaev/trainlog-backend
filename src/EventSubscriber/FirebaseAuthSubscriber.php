<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\ApiError;
use App\Api\ApiResponse;
use App\Service\FirebaseIdTokenVerifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
final class FirebaseAuthSubscriber implements EventSubscriberInterface
{
    public const FIREBASE_UID_ATTRIBUTE = 'firebase_uid';

    public function __construct(
        private readonly FirebaseIdTokenVerifier $verifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if ($route === null) {
            return;
        }
        // Only protect api_v1_* routes (except health)
        if ($route === 'api_v1_health') {
            return;
        }
        if (str_starts_with((string) $route, 'api_v1_') === false) {
            return;
        }
        // Require auth for all other api_v1_* routes
        $auth = $request->headers->get('Authorization');
        if ($auth === null || !str_starts_with($auth, 'Bearer ')) {
            $event->setResponse(ApiResponse::error(ApiError::MissingOrInvalidAuthHeader));
            return;
        }
        $token = trim(substr($auth, 7));
        if ($token === '') {
            $event->setResponse(ApiResponse::error(ApiError::MissingOrInvalidAuthHeader));
            return;
        }
        try {
            $claims = $this->verifier->verify($token);
            $request->attributes->set(self::FIREBASE_UID_ATTRIBUTE, $claims['sub']);
        } catch (\Throwable $e) {
            $event->setResponse(ApiResponse::error(
                ApiError::InvalidOrExpiredToken,
                Response::HTTP_UNAUTHORIZED,
                ['message' => $e->getMessage()]
            ));
        }
    }
}
