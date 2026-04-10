<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Controller\Api\AbstractApiController;
use App\Http\Request\Support\ClaimSupportRewardRequest;
use App\Http\Request\Support\CreateSupportCampaignRequest;
use App\Service\Api\SupportCampaignAppService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/support', name: 'api_v1_support_')]
final class SupportCampaignController extends AbstractApiController
{
    public function __construct(
        private readonly SupportCampaignAppService $supportCampaignAppService,
    ) {
    }

    #[Route('/campaign', name: 'campaign_get', methods: ['GET'])]
    public function getCampaign(Request $request): JsonResponse
    {
        return ApiResponse::success($this->supportCampaignAppService->getCampaign($this->getUserId($request)));
    }

    #[Route('/campaign/new', name: 'campaign_new', methods: ['POST'])]
    public function createNewCampaign(#[MapRequestPayload] CreateSupportCampaignRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->supportCampaignAppService->createNewCampaign($this->getUserId($request), $payload->toArray()));
    }

    #[Route('/campaign/reward-claim', name: 'campaign_reward_claim', methods: ['POST'])]
    public function claimReward(#[MapRequestPayload] ClaimSupportRewardRequest $payload, Request $request): JsonResponse
    {
        return ApiResponse::success($this->supportCampaignAppService->claimReward($this->getUserId($request), $payload->toArray()));
    }
}

