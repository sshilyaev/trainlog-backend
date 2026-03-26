<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\ConnectionToken\CreateConnectionTokenRequest;
use App\Http\Request\ConnectionToken\PreviewConnectionTokenRequest;
use App\Http\Request\ConnectionToken\UseConnectionTokenRequest;
use App\Service\Api\ConnectionTokenAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class ConnectionTokenController extends AbstractApiController
{
    public function __construct(
        private readonly ConnectionTokenAppService $connectionTokenAppService,
    ) {
    }

    #[Route('/connection-tokens', name: 'connection_tokens_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateConnectionTokenRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->connectionTokenAppService->create($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/connection-tokens/use', name: 'connection_tokens_use', methods: ['POST'])]
    public function use(#[MapRequestPayload] UseConnectionTokenRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->connectionTokenAppService->use($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/connection-tokens/preview', name: 'connection_tokens_preview', methods: ['GET'])]
    public function preview(#[MapQueryString] PreviewConnectionTokenRequest $query, Request $request): JsonResponse
    {
        $this->getUserId($request);
        return ApiResponse::success($this->connectionTokenAppService->preview($query->token));
    }
}
