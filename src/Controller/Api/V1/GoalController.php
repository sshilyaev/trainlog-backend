<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Goal\CreateGoalRequest;
use App\Http\Request\Goal\ListGoalsRequest;
use App\Http\Request\Goal\UpdateGoalRequest;
use App\Service\Api\GoalAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class GoalController extends AbstractApiController
{
    public function __construct(
        private readonly GoalAppService $goalAppService,
    ) {
    }

    #[Route('/goals', name: 'goals_list', methods: ['GET'])]
    public function list(#[MapQueryString] ListGoalsRequest $query, Request $request): JsonResponse
    {
        return ApiResponse::success($this->goalAppService->list($query->profileId, $this->getUserId($request)));
    }

    #[Route('/goals', name: 'goals_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateGoalRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->goalAppService->create($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/goals/{id}', name: 'goals_get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->goalAppService->get($id, $this->getUserId($request)));
    }

    #[Route('/goals/{id}', name: 'goals_update', methods: ['PATCH', 'PUT'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $id, #[MapRequestPayload] UpdateGoalRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->goalAppService->update($id, $this->getUserId($request), $payload->toArray()));
    }

    #[Route('/goals/{id}', name: 'goals_delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function delete(string $id, Request $request): JsonResponse|Response
    {
        $this->goalAppService->delete($id, $this->getUserId($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
