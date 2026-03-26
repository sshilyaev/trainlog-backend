<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Calendar\CalendarFeedRequest;
use App\Service\Api\CalendarAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_')]
final class CalendarController extends AbstractApiController
{
    public function __construct(
        private readonly CalendarAppService $calendarAppService,
    ) {
    }

    #[Route('/calendar', name: 'calendar_feed', methods: ['GET'])]
    public function feed(#[MapQueryString] CalendarFeedRequest $query, Request $request): JsonResponse
    {
        return ApiResponse::success($this->calendarAppService->getCalendar(
            $query->coachProfileId,
            $query->traineeProfileId,
            $query->from,
            $query->to,
            $this->getUserId($request)
        ));
    }
}
