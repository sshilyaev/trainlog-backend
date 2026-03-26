<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Api\ApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof UnprocessableEntityHttpException) {
            $previous = $throwable->getPrevious();
            if ($previous instanceof ValidationFailedException) {
                $messages = array_map(
                    static fn ($v) => $v->getMessage(),
                    iterator_to_array($previous->getViolations())
                );
                $event->setResponse(ApiResponse::error(
                    ApiError::ValidationFailed,
                    Response::HTTP_BAD_REQUEST,
                    ['messages' => array_values($messages)]
                ));
                return;
            }
        }

        if (!$throwable instanceof ApiException) {
            return;
        }
        $event->setResponse(ApiResponse::error(
            $throwable->getError(),
            $throwable->getHttpStatus(),
            $throwable->getDetails()
        ));
    }
}
