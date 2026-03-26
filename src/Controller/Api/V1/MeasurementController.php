<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Measurement\CreateMeasurementRequest;
use App\Http\Request\Measurement\UpdateMeasurementRequest;
use App\Service\Api\MeasurementAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class MeasurementController extends AbstractApiController
{
    public function __construct(
        private readonly MeasurementAppService $measurementAppService,
    ) {
    }

    #[Route('/profiles/{profileId}/measurements', name: 'measurements_list', methods: ['GET'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}'])]
    public function list(string $profileId, Request $request): JsonResponse
    {
        return ApiResponse::success($this->measurementAppService->list($profileId, $this->getUserId($request)));
    }

    #[Route('/profiles/{profileId}/measurements', name: 'measurements_create', methods: ['POST'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}'])]
    public function create(string $profileId, #[MapRequestPayload] CreateMeasurementRequest $payload, Request $request): JsonResponse
    {
        $data = $payload->toArray();
        $data['profileId'] = $profileId;
        return ApiResponse::success(
            $this->measurementAppService->create($this->getUserId($request), $data),
            Response::HTTP_CREATED
        );
    }

    #[Route('/profiles/{profileId}/measurements/{id}', name: 'measurements_get', methods: ['GET'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}', 'id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $profileId, string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->measurementAppService->get($id, $profileId, $this->getUserId($request)));
    }

    #[Route('/profiles/{profileId}/measurements/{id}', name: 'measurements_update', methods: ['PATCH', 'PUT'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}', 'id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $profileId, string $id, #[MapRequestPayload] UpdateMeasurementRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->measurementAppService->update($id, $profileId, $this->getUserId($request), $payload->toArray()));
    }

    #[Route('/profiles/{profileId}/measurements/{id}', name: 'measurements_delete', methods: ['DELETE'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}', 'id' => '[0-9a-fA-F\-]{36}'])]
    public function delete(string $profileId, string $id, Request $request): JsonResponse|Response
    {
        $this->measurementAppService->delete($id, $profileId, $this->getUserId($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
