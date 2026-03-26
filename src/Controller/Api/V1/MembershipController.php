<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Membership\CreateMembershipRequest;
use App\Http\Request\Membership\ListMembershipsRequest;
use App\Http\Request\Membership\UpdateMembershipRequest;
use App\Service\Api\MembershipAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class MembershipController extends AbstractApiController
{
    public function __construct(
        private readonly MembershipAppService $membershipAppService,
    ) {
    }

    #[Route('/memberships', name: 'memberships_list', methods: ['GET'])]
    public function list(#[MapQueryString] ListMembershipsRequest $query, Request $request): JsonResponse
    {
        return ApiResponse::success($this->membershipAppService->list(
            $query->coachProfileId !== null && $query->coachProfileId !== '' ? $query->coachProfileId : null,
            $query->traineeProfileId !== null && $query->traineeProfileId !== '' ? $query->traineeProfileId : null,
            $this->getUserId($request)
        ));
    }

    #[Route('/memberships', name: 'memberships_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateMembershipRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->membershipAppService->create($this->getUserId($request), $payload->toArray()),
            Response::HTTP_CREATED
        );
    }

    #[Route('/memberships/{id}', name: 'memberships_get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function get(string $id, Request $request): JsonResponse
    {
        return ApiResponse::success($this->membershipAppService->get($id, $this->getUserId($request)));
    }

    #[Route('/memberships/{id}', name: 'memberships_update', methods: ['PATCH', 'PUT'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function update(string $id, #[MapRequestPayload] UpdateMembershipRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->membershipAppService->update($id, $this->getUserId($request), $payload->toArray()));
    }
}
