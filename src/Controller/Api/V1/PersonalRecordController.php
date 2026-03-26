<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Record\CreatePersonalRecordRequest;
use App\Http\Request\Record\UpdatePersonalRecordRequest;
use App\Service\Api\PersonalRecordAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class PersonalRecordController extends AbstractApiController
{
    public function __construct(
        private readonly PersonalRecordAppService $personalRecordAppService,
    ) {
    }

    #[Route('/profiles/{profileId}/records', name: 'personal_records_list', methods: ['GET'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}'])]
    public function list(string $profileId, Request $request): JsonResponse
    {
        return ApiResponse::success($this->personalRecordAppService->list($profileId, $this->getUserId($request)));
    }

    #[Route('/profiles/{profileId}/records', name: 'personal_records_create', methods: ['POST'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}'])]
    public function create(string $profileId, #[MapRequestPayload] CreatePersonalRecordRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->personalRecordAppService->create($profileId, $this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/profiles/{profileId}/records/{id}', name: 'personal_records_get', methods: ['GET'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}', 'id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $profileId, string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->personalRecordAppService->get($id, $profileId, $this->getUserId($request)));
    }

    #[Route('/profiles/{profileId}/records/{id}', name: 'personal_records_update', methods: ['PATCH', 'PUT'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}', 'id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $profileId, string $id, #[MapRequestPayload] UpdatePersonalRecordRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->personalRecordAppService->update($id, $profileId, $this->getUserId($request), $payload->toArray()));
    }

    #[Route('/profiles/{profileId}/records/{id}', name: 'personal_records_delete', methods: ['DELETE'], requirements: ['profileId' => '[0-9a-fA-F\-]{36}', 'id' => '[0-9a-fA-F\-]{36}'])]
    public function delete(string $profileId, string $id, Request $request): Response
    {
        $this->personalRecordAppService->delete($id, $profileId, $this->getUserId($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/records/activities', name: 'record_activities_list', methods: ['GET'])]
    public function listActivities(Request $request): JsonResponse
    {
        $q = $request->query->get('q');
        $activityType = $request->query->get('activityType');
        $limit = (int) ($request->query->get('limit') ?? 200);
        $offset = (int) ($request->query->get('offset') ?? 0);

        return ApiResponse::success($this->personalRecordAppService->listActivities(
            is_string($q) ? $q : null,
            is_string($activityType) ? $activityType : null,
            $limit,
            $offset
        ));
    }
}
