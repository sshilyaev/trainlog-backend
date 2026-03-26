<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\NutritionPlan\CreateNutritionPlanRequest;
use App\Http\Request\NutritionPlan\ListNutritionPlansRequest;
use App\Http\Request\NutritionPlan\UpdateNutritionPlanRequest;
use App\Service\Api\NutritionPlanAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class NutritionPlanController extends AbstractApiController
{
    public function __construct(
        private readonly NutritionPlanAppService $nutritionPlanAppService,
    ) {
    }

    #[Route('/nutrition-plans', name: 'nutrition_plans_list', methods: ['GET'])]
    public function list(#[MapQueryString] ListNutritionPlansRequest $query, Request $request): JsonResponse
    {
        return ApiResponse::success($this->nutritionPlanAppService->list(
            $query->coachProfileId !== null && $query->coachProfileId !== '' ? $query->coachProfileId : null,
            $query->traineeProfileId !== null && $query->traineeProfileId !== '' ? $query->traineeProfileId : null,
            $query->as,
            $query->embed,
            $this->getUserId($request)
        ));
    }

    #[Route('/nutrition-plans', name: 'nutrition_plans_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateNutritionPlanRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->nutritionPlanAppService->create($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/nutrition-plans/{id}', name: 'nutrition_plans_update', methods: ['PATCH'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $id, #[MapRequestPayload] UpdateNutritionPlanRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->nutritionPlanAppService->update($id, $this->getUserId($request), $payload->toArray()));
    }
}

