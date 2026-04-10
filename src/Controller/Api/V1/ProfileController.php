<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Profile\CreateProfileRequest;
use App\Http\Request\Profile\MergeProfilesRequest;
use App\Http\Request\Profile\UpdateProfileRequest;
use App\Service\Api\CoachStatisticsAppService;
use App\Service\Api\MergeProfileAppService;
use App\Service\Api\ProfileAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class ProfileController extends AbstractApiController
{
    public function __construct(
        private readonly ProfileAppService $profileAppService,
        private readonly MergeProfileAppService $mergeProfileAppService,
        private readonly CoachStatisticsAppService $coachStatisticsAppService,
    ) {
    }

    #[Route('/profiles', name: 'profiles_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return ApiResponse::success($this->profileAppService->list($this->getUserId($request)));
    }

    #[Route('/profiles', name: 'profiles_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProfileRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->profileAppService->create($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/profiles/{id}', name: 'profiles_get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->profileAppService->get($id, $this->getUserId($request)));
    }

    #[Route('/profiles/{coachProfileId}/statistics', name: 'profiles_statistics', methods: ['GET'], requirements: ['coachProfileId' => '[0-9a-fA-F\-]{36}'])]
    public function statistics(string $coachProfileId, Request $request): JsonResponse
    {
        $month = $request->query->get('month') ?? (new \DateTimeImmutable('now'))->format('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = (new \DateTimeImmutable('now'))->format('Y-m');
        }
        $months = (int) ($request->query->get('months') ?? 1);
        if (!\in_array($months, [1, 3, 6], true)) {
            $months = 1;
        }

        return ApiResponse::success($this->coachStatisticsAppService->getStatistics($coachProfileId, $month, $this->getUserId($request), $months));
    }

    #[Route('/profiles/{id}', name: 'profiles_update', methods: ['PATCH', 'PUT'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $id, #[MapRequestPayload] UpdateProfileRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->profileAppService->update($id, $this->getUserId($request), $payload->toArray()));
    }

    #[Route('/profiles/{id}', name: 'profiles_delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function delete(string $id, Request $request): JsonResponse|Response
    {
        $this->profileAppService->delete($id, $this->getUserId($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/profiles/merge', name: 'profiles_merge', methods: ['POST'])]
    public function merge(#[MapRequestPayload] MergeProfilesRequest $payload, Request $request): Response
    {
        $this->mergeProfileAppService->merge($this->getUserId($request), $payload->toArray());
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
