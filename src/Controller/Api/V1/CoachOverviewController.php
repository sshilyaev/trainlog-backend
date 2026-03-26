<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\CoachOverview\ListCoachTraineesOverviewRequest;
use App\Service\Api\CoachOverviewAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class CoachOverviewController extends AbstractApiController
{
    public function __construct(
        private readonly CoachOverviewAppService $coachOverviewAppService,
    ) {
    }

    #[Route('/coach-profiles/{coachProfileId}/trainees/overview', name: 'coach_trainees_overview', methods: ['GET'], requirements: ['coachProfileId' => '[0-9a-fA-F\-]{36}'])]
    public function list(
        string $coachProfileId,
        #[MapQueryString] ListCoachTraineesOverviewRequest $query,
        Request $request
    ): JsonResponse|Response {
        $payload = $this->coachOverviewAppService->listCoachTraineesOverview(
            $coachProfileId,
            $this->getUserId($request),
            $query->includeArchived,
            max(1, $query->page),
            min(200, max(1, $query->limit))
        );
        $etag = '"' . md5((string) json_encode($payload)) . '"';
        if ($request->headers->get('If-None-Match') === $etag) {
            $notModified = new Response('', Response::HTTP_NOT_MODIFIED);
            $notModified->headers->set('ETag', $etag);
            $notModified->headers->set('Cache-Control', 'private, max-age=30');
            return $notModified;
        }
        $response = ApiResponse::success($payload);
        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'private, max-age=30');
        return $response;
    }
}
