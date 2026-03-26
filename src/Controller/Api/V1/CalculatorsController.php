<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Calculators\CalculateCalculatorRequest;
use App\Service\Api\CalculatorsAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class CalculatorsController extends AbstractApiController
{
    public function __construct(
        private readonly CalculatorsAppService $calculatorsAppService,
    ) {
    }

    #[Route('/calculators/catalog', name: 'calculators_catalog', methods: ['GET'])]
    public function catalog(Request $request): JsonResponse
    {
        return ApiResponse::success($this->calculatorsAppService->catalog($this->getUserId($request)));
    }

    #[Route('/calculators/{id}/definition', name: 'calculators_definition', methods: ['GET'], requirements: ['id' => '[a-zA-Z0-9_\\-]{1,80}'])]
    public function definition(string $id): JsonResponse
    {
        return ApiResponse::success($this->calculatorsAppService->definition($id));
    }

    #[Route('/calculators/{id}/calculate', name: 'calculators_calculate', methods: ['POST'], requirements: ['id' => '[a-zA-Z0-9_\\-]{1,80}'])]
    public function calculate(string $id, #[MapRequestPayload] CalculateCalculatorRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->calculatorsAppService->calculate($id, $payload->profileId, $payload->inputs),
            Response::HTTP_OK,
        );
    }
}

