<?php

declare(strict_types=1);

namespace App\Api;

use App\Enum\ApiError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    /**
     * Успешный ответ с данными.
     *
     * @param array<string, mixed> $data
     */
    public static function success(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    /**
     * Ответ об ошибке по коду из ApiError.
     *
     * @param array<string, mixed> $details Доп. поля (например messages для валидации)
     */
    public static function error(ApiError $error, ?int $status = null, array $details = []): JsonResponse
    {
        $status ??= $error->httpStatus();
        $body = [
            'error' => $error->message(),
            'code' => $error->value,
        ];
        if ($details !== []) {
            $body = array_merge($body, $details);
        }
        return new JsonResponse($body, $status);
    }
}
