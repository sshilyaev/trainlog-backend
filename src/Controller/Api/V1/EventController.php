<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Event\CreateEventRequest;
use App\Http\Request\Event\ListEventsRequest;
use App\Http\Request\Event\UpdateEventRequest;
use App\Service\Api\EventAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class EventController extends AbstractApiController
{
    public function __construct(
        private readonly EventAppService $eventAppService,
    ) {
    }

    #[Route('/events', name: 'events_list', methods: ['GET'])]
    public function list(#[MapQueryString] ListEventsRequest $query, Request $request): JsonResponse
    {
        $limit = $query->limit;
        if ($limit !== null && $limit < 1) {
            $limit = null;
        }
        if ($limit !== null && $limit > 200) {
            $limit = 200;
        }
        return ApiResponse::success($this->eventAppService->list(
            $query->coachProfileId !== null && $query->coachProfileId !== '' ? $query->coachProfileId : null,
            $query->traineeProfileId !== null && $query->traineeProfileId !== '' ? $query->traineeProfileId : null,
            $query->profileId !== null && $query->profileId !== '' ? $query->profileId : null,
            $this->getUserId($request),
            $limit,
            $query->after !== null && $query->after !== '' ? $query->after : null
        ));
    }

    #[Route('/events', name: 'events_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateEventRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->eventAppService->create($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/events/{id}', name: 'events_get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->eventAppService->get($id, $this->getUserId($request)));
    }

    #[Route('/events/{id}', name: 'events_update', methods: ['PATCH', 'PUT'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $id, #[MapRequestPayload] UpdateEventRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->eventAppService->update($id, $this->getUserId($request), $payload->toArray()));
    }

    #[Route('/events/{id}', name: 'events_delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function delete(string $id, Request $request): JsonResponse|Response
    {
        $this->eventAppService->delete($id, $this->getUserId($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
