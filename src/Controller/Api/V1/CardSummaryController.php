<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\CardSummary\CardSummaryQueryRequest;
use App\Service\Api\CardSummaryAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class CardSummaryController extends AbstractApiController
{
    public function __construct(
        private readonly CardSummaryAppService $cardSummaryAppService,
    ) {
    }

    #[Route('/coach-profiles/{coachProfileId}/trainees/{traineeProfileId}/card-summary', name: 'card_summary', methods: ['GET'], requirements: ['coachProfileId' => '[0-9a-fA-F\-]{36}', 'traineeProfileId' => '[0-9a-fA-F\-]{36}'])]
    public function cardSummary(
        string $coachProfileId,
        string $traineeProfileId,
        #[MapQueryString] CardSummaryQueryRequest $query,
        Request $request
    ): JsonResponse {
        return ApiResponse::success($this->cardSummaryAppService->getCardSummary(
            $coachProfileId,
            $traineeProfileId,
            $this->getUserId($request),
            $query->calendarFrom !== null && $query->calendarFrom !== '' ? $query->calendarFrom : null,
            $query->calendarTo !== null && $query->calendarTo !== '' ? $query->calendarTo : null
        ));
    }
}
