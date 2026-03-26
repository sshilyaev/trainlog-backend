<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Supplement\CreateSupplementAssignmentRequest;
use App\Http\Request\Supplement\ListSupplementAssignmentsRequest;
use App\Http\Request\Supplement\ListSupplementCatalogRequest;
use App\Http\Request\Supplement\UpdateSupplementAssignmentRequest;
use App\Service\Api\SupplementAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class SupplementController extends AbstractApiController
{
    public function __construct(
        private readonly SupplementAppService $supplementAppService,
    ) {
    }

    #[Route('/supplements/catalog', name: 'supplements_catalog_list', methods: ['GET'])]
    public function listCatalog(#[MapQueryString] ListSupplementCatalogRequest $query): JsonResponse
    {
        return ApiResponse::success($this->supplementAppService->listCatalog($query->type));
    }

    #[Route('/supplements/assignments', name: 'supplements_assignments_list', methods: ['GET'])]
    public function listAssignments(#[MapQueryString] ListSupplementAssignmentsRequest $query, Request $request): JsonResponse
    {
        return ApiResponse::success($this->supplementAppService->listAssignments(
            $query->coachProfileId,
            $query->traineeProfileId,
            $query->as,
            $this->getUserId($request)
        ));
    }

    #[Route('/supplements/assignments', name: 'supplements_assignments_create', methods: ['POST'])]
    public function createAssignment(#[MapRequestPayload] CreateSupplementAssignmentRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->supplementAppService->createAssignment($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/supplements/assignments/{id}', name: 'supplements_assignments_update', methods: ['PATCH', 'PUT'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function updateAssignment(string $id, #[MapRequestPayload] UpdateSupplementAssignmentRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->supplementAppService->updateAssignmentFromPayload(
            $id,
            $this->getUserId($request),
            $payload,
            (string) $request->getContent()
        ));
    }

    #[Route('/supplements/assignments/{id}', name: 'supplements_assignments_delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function deleteAssignment(string $id, Request $request): Response
    {
        $this->supplementAppService->deleteAssignment($id, $this->getUserId($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}

