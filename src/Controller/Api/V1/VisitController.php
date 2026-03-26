<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Visit\CreateVisitRequest;
use App\Http\Request\Visit\ListVisitsRequest;
use App\Http\Request\Visit\UpdateVisitRequest;
use App\Service\Api\VisitAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class VisitController extends AbstractApiController
{
    public function __construct(
        private readonly VisitAppService $visitAppService,
    ) {
    }

    #[Route('/visits', name: 'visits_list', methods: ['GET'])]
    public function list(#[MapQueryString] ListVisitsRequest $query, Request $request): JsonResponse
    {
        $limit = $query->limit;
        if ($limit !== null && $limit < 1) {
            $limit = null;
        }
        if ($limit !== null && $limit > 200) {
            $limit = 200;
        }
        return ApiResponse::success($this->visitAppService->list(
            $query->coachProfileId !== null && $query->coachProfileId !== '' ? $query->coachProfileId : null,
            $query->traineeProfileId !== null && $query->traineeProfileId !== '' ? $query->traineeProfileId : null,
            $query->month !== null && $query->month !== '' ? $query->month : null,
            $this->getUserId($request),
            $limit,
            $query->after !== null && $query->after !== '' ? $query->after : null
        ));
    }

    #[Route('/visits', name: 'visits_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateVisitRequest $payload, Request $request): JsonResponse
    {
        $result = $this->visitAppService->create($this->getUserId($request), $payload->toArray());
        // 201 for both new and idempotent replay (per API optimization requirements)
        return ApiResponse::success($result['visit'], Response::HTTP_CREATED);
    }

    #[Route('/visits/{id}', name: 'visits_get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->visitAppService->get($id, $this->getUserId($request)));
    }

    #[Route('/visits/{id}', name: 'visits_update', methods: ['PATCH', 'PUT'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $id, #[MapRequestPayload] UpdateVisitRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->visitAppService->update($id, $this->getUserId($request), $payload->toArray()));
    }
}
