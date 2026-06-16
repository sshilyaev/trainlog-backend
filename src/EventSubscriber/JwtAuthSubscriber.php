<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Api\ApiResponse;
use App\Enum\ApiError;
use App\Service\JwtService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class JwtAuthSubscriber implements EventSubscriberInterface
{
    public const USER_ID_ATTRIBUTE = 'firebase_uid';

    public function __construct(private readonly JwtService $jwtService)
    {
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

        // Public routes — skip auth entirely
        if ($route === 'api_v1_health') {
            return;
        }
        if (str_starts_with((string) $route, 'api_v1_auth_')) {
            return;
        }
        if (!str_starts_with((string) $route, 'api_v1_')) {
            return;
        }

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
            $claims = $this->jwtService->verify($token, 'access');
            $request->attributes->set(self::USER_ID_ATTRIBUTE, $claims['sub']);
        } catch (\Throwable $e) {
            $event->setResponse(ApiResponse::error(
                ApiError::InvalidOrExpiredToken,
                Response::HTTP_UNAUTHORIZED,
                ['message' => $e->getMessage()]
            ));
        }
    }
}
