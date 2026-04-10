<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\SupportCampaignHistory;
use App\Entity\SupportCampaignState;
use App\Entity\SupportRewardEvent;
use App\Repository\SupportCampaignHistoryRepository;
use App\Repository\SupportCampaignStateRepository;
use App\Repository\SupportRewardEventRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SupportCampaignAppService
{
    public function __construct(
        private readonly SupportCampaignStateRepository $stateRepository,
        private readonly SupportCampaignHistoryRepository $historyRepository,
        private readonly SupportRewardEventRepository $rewardEventRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array<string, mixed> */
    public function getCampaign(string $userId): array
    {
        $state = $this->stateRepository->findByUserId($userId);
        if ($state === null) {
            $state = $this->createNewStateForUser($userId, null);
            $this->em->persist($state);
            $this->em->flush();
        }

        return $this->buildResponse($state, false, null);
    }

    /**
     * @param array{goalType?: ?string} $data
     * @return array<string, mixed>
     */
    public function createNewCampaign(string $userId, array $data): array
    {
        $goalType = isset($data['goalType']) && \in_array($data['goalType'], [SupportCampaignState::GOAL_LOSE, SupportCampaignState::GOAL_GAIN], true)
            ? $data['goalType']
            : null;

        $state = $this->stateRepository->findByUserId($userId);
        if ($state === null) {
            $state = $this->createNewStateForUser($userId, $goalType);
            $this->em->persist($state);
            $this->em->flush();

            return $this->buildResponse($state, false, null);
        }

        $saved = $state->getSavedClientsCount();
        $newState = $this->createNewStateForUser($userId, $goalType)
            ->setSavedClientsCount($saved);

        $this->em->remove($state);
        $this->em->persist($newState);
        $this->em->flush();

        return $this->buildResponse($newState, false, null);
    }

    /**
     * @param array{adProvider: string, externalEventId: string, rewardValueKg: float|int|string} $data
     * @return array<string, mixed>
     */
    public function claimReward(string $userId, array $data): array
    {
        $adProvider = trim((string) ($data['adProvider'] ?? ''));
        $externalEventId = trim((string) ($data['externalEventId'] ?? ''));
        $rewardValueKg = (float) ($data['rewardValueKg'] ?? 1.0);
        $rewardValueKg = max(0.1, min(5.0, $rewardValueKg));

        $existingEvent = $this->rewardEventRepository->findByUserProviderAndExternalId($userId, $adProvider, $externalEventId);
        if ($existingEvent !== null) {
            $state = $this->stateRepository->findByUserId($userId);
            if ($state === null) {
                $state = $this->createNewStateForUser($userId, null);
                $this->em->persist($state);
                $this->em->flush();
            }

            return $this->buildResponse($state, true, $existingEvent->getId());
        }

        $this->em->getConnection()->transactional(function () use ($userId, $adProvider, $externalEventId, $rewardValueKg): void {
            $state = $this->stateRepository->findByUserId($userId);
            if ($state === null) {
                $state = $this->createNewStateForUser($userId, null);
                $this->em->persist($state);
            }

            $isCompleted = false;
            if ($state->getStatus() === SupportCampaignState::STATUS_ACTIVE) {
                if ($state->getGoalType() === SupportCampaignState::GOAL_LOSE) {
                    $nextWeight = max($state->getTargetWeightKg(), $state->getCurrentWeightKg() - $rewardValueKg);
                } else {
                    $nextWeight = min($state->getTargetWeightKg(), $state->getCurrentWeightKg() + $rewardValueKg);
                }
                $state->setCurrentWeightKg($nextWeight);

                $isCompleted =
                    ($state->getGoalType() === SupportCampaignState::GOAL_LOSE && $nextWeight <= $state->getTargetWeightKg())
                    || ($state->getGoalType() === SupportCampaignState::GOAL_GAIN && $nextWeight >= $state->getTargetWeightKg());

                if ($isCompleted) {
                    $state->setStatus(SupportCampaignState::STATUS_COMPLETED);
                    $state->incrementSavedClientsCount();
                    $history = (new SupportCampaignHistory())
                        ->setUserId($userId)
                        ->setGoalType($state->getGoalType())
                        ->setStartWeightKg($state->getStartWeightKg())
                        ->setTargetWeightKg($state->getTargetWeightKg());
                    $this->em->persist($history);
                }
            }
            $state->touch();
            $this->em->persist($state);

            $event = (new SupportRewardEvent())
                ->setUserId($userId)
                ->setAdProvider($adProvider)
                ->setExternalEventId($externalEventId)
                ->setRewardValueKg($rewardValueKg)
                ->setIsDuplicate(false);
            $this->em->persist($event);
            $this->em->flush();
        });

        $state = $this->stateRepository->findByUserId($userId);
        \assert($state instanceof SupportCampaignState);
        $event = $this->rewardEventRepository->findByUserProviderAndExternalId($userId, $adProvider, $externalEventId);

        return $this->buildResponse($state, false, $event?->getId());
    }

    /** @return array<string, mixed> */
    private function buildResponse(SupportCampaignState $state, bool $isIdempotent, ?string $rewardEventId): array
    {
        $history = $this->historyRepository->findRecentByUserId($state->getUserId(), 20);

        return [
            'campaign' => [
                'id' => $state->getId(),
                'goalType' => $state->getGoalType(),
                'status' => $state->getStatus(),
                'startWeightKg' => $state->getStartWeightKg(),
                'currentWeightKg' => $state->getCurrentWeightKg(),
                'targetWeightKg' => $state->getTargetWeightKg(),
                'savedClientsCount' => $state->getSavedClientsCount(),
                'updatedAt' => $state->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'history' => array_map(static fn (SupportCampaignHistory $h): array => [
                'id' => $h->getId(),
                'goalType' => $h->getGoalType(),
                'startWeightKg' => $h->getStartWeightKg(),
                'targetWeightKg' => $h->getTargetWeightKg(),
                'createdAt' => $h->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $history),
            'meta' => [
                'rewardEventsTotal' => $this->rewardEventRepository->countByUserId($state->getUserId()),
                'idempotent' => $isIdempotent,
                'rewardEventId' => $rewardEventId,
            ],
        ];
    }

    private function createNewStateForUser(string $userId, ?string $goalType): SupportCampaignState
    {
        $goal = $goalType;
        if ($goal === null) {
            $goal = random_int(0, 1) === 0 ? SupportCampaignState::GOAL_LOSE : SupportCampaignState::GOAL_GAIN;
        }

        if ($goal === SupportCampaignState::GOAL_GAIN) {
            $start = random_int(45, 85);
            $target = min($start + random_int(4, 12), 120);
        } else {
            $start = random_int(70, 130);
            $target = max($start - random_int(4, 16), 45);
        }

        return (new SupportCampaignState())
            ->setUserId($userId)
            ->setGoalType($goal)
            ->setStatus(SupportCampaignState::STATUS_ACTIVE)
            ->setStartWeightKg((float) $start)
            ->setCurrentWeightKg((float) $start)
            ->setTargetWeightKg((float) $target);
    }
}

