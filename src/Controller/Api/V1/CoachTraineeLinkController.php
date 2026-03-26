<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\CoachTraineeLink\CreateCoachTraineeLinkRequest;
use App\Http\Request\CoachTraineeLink\ListCoachTraineeLinksRequest;
use App\Http\Request\CoachTraineeLink\UpdateCoachTraineeLinkRequest;
use App\Service\Api\CoachTraineeLinkAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class CoachTraineeLinkController extends AbstractApiController
{
    public function __construct(
        private readonly CoachTraineeLinkAppService $coachTraineeLinkAppService,
    ) {
    }

    #[Route('/coach-trainee-links', name: 'coach_trainee_links_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateCoachTraineeLinkRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->coachTraineeLinkAppService->create($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/coach-trainee-links', name: 'coach_trainee_links_list', methods: ['GET'])]
    public function list(#[MapQueryString] ListCoachTraineeLinksRequest $query, Request $request): JsonResponse
    {
        $embedProfiles = $query->embed === 'profiles';
        return ApiResponse::success($this->coachTraineeLinkAppService->list(
            $query->profileId,
            $query->as,
            $this->getUserId($request),
            $embedProfiles
        ));
    }

    #[Route('/coach-trainee-links/{id}', name: 'coach_trainee_links_get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->coachTraineeLinkAppService->get($id, $this->getUserId($request)));
    }

    #[Route('/coach-trainee-links/{id}', name: 'coach_trainee_links_update', methods: ['PATCH', 'PUT'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $id, #[MapRequestPayload] UpdateCoachTraineeLinkRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->coachTraineeLinkAppService->update($id, $this->getUserId($request), $payload->toArray()));
    }

    #[Route('/coach-trainee-links/{id}', name: 'coach_trainee_links_delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function delete(string $id, Request $request): JsonResponse|Response
    {
        $this->coachTraineeLinkAppService->delete($id, $this->getUserId($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
