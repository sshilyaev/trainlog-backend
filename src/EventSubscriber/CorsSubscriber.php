<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CorsSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ORIGINS = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'https://app.train.tallybase.ru',
    ];

    private const ALLOWED_HEADERS = [
        'Authorization',
        'Content-Type',
        'Accept',
        'Idempotency-Key',
        'If-None-Match',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiRequest($request)) {
            return;
        }

        $origin = $request->headers->get('Origin');
        if ($origin === null || !$this->isAllowedOrigin($origin)) {
            return;
        }

        if ($request->getMethod() === Request::METHOD_OPTIONS) {
            $response = new Response('', Response::HTTP_NO_CONTENT);
            $this->applyCorsHeaders($response, $origin);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiRequest($request)) {
            return;
        }

        $origin = $request->headers->get('Origin');
        if ($origin === null || !$this->isAllowedOrigin($origin)) {
            return;
        }

        $this->applyCorsHeaders($event->getResponse(), $origin);
    }

    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/');
    }

    private function isAllowedOrigin(string $origin): bool
    {
        return in_array($origin, self::ALLOWED_ORIGINS, true);
    }

    private function applyCorsHeaders(Response $response, string $origin): void
    {
        $headers = $response->headers;
        $headers->set('Access-Control-Allow-Origin', $origin);
        $headers->set('Access-Control-Allow-Credentials', 'true');
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $headers->set('Access-Control-Allow-Headers', implode(', ', self::ALLOWED_HEADERS));
        $headers->set('Access-Control-Expose-Headers', 'ETag');
        $headers->set('Vary', 'Origin', false);
    }
}
