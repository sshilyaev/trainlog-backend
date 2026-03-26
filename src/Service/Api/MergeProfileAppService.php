<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Repository\ProfileRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class MergeProfileAppService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @param array{coachProfileId: string, managedTraineeProfileId: string, realTraineeProfileId: string} $data */
    public function merge(string $userId, array $data): void
    {
        $coachProfileId = $data['coachProfileId'] ?? '';
        $managedTraineeProfileId = $data['managedTraineeProfileId'] ?? '';
        $realTraineeProfileId = $data['realTraineeProfileId'] ?? '';
        if ($coachProfileId === '' || $managedTraineeProfileId === '' || $realTraineeProfileId === '') {
            throw new ApiException(ApiError::MergeProfileParamsRequired);
        }
        if ($managedTraineeProfileId === $realTraineeProfileId) {
            throw new ApiException(ApiError::MergeProfileSameIds);
        }
        $coachProfile = $this->profileRepository->find($coachProfileId);
        if ($coachProfile === null || $coachProfile->getUserId() !== $userId) {
            throw new ApiException(ApiError::MergeProfileOnlyCoach);
        }
        $managedProfile = $this->profileRepository->find($managedTraineeProfileId);
        if ($managedProfile === null) {
            throw new ApiException(ApiError::MergeProfileManagedNotFound);
        }
        if ($managedProfile->getMergedIntoProfileId() !== null) {
            throw new ApiException(ApiError::MergeProfileAlreadyMerged);
        }
        $realProfile = $this->profileRepository->find($realTraineeProfileId);
        if ($realProfile === null) {
            throw new ApiException(ApiError::MergeProfileRealNotFound);
        }
        if (!$this->profileAccessChecker->canAccess($realTraineeProfileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }

        $conn = $this->connection;
        $conn->beginTransaction();
        try {
            $conn->executeStatement(
                'UPDATE measurements SET profile_id = ? WHERE profile_id = ?',
                [$realTraineeProfileId, $managedTraineeProfileId]
            );
            $conn->executeStatement(
                'UPDATE goals SET profile_id = ? WHERE profile_id = ?',
                [$realTraineeProfileId, $managedTraineeProfileId]
            );
            $conn->executeStatement(
                'UPDATE personal_records SET profile_id = ? WHERE profile_id = ?',
                [$realTraineeProfileId, $managedTraineeProfileId]
            );
            $conn->executeStatement(
                'UPDATE memberships SET trainee_profile_id = ? WHERE coach_profile_id = ? AND trainee_profile_id = ?',
                [$realTraineeProfileId, $coachProfileId, $managedTraineeProfileId]
            );
            $conn->executeStatement(
                'UPDATE visits SET trainee_profile_id = ? WHERE coach_profile_id = ? AND trainee_profile_id = ?',
                [$realTraineeProfileId, $coachProfileId, $managedTraineeProfileId]
            );
            $conn->executeStatement(
                'UPDATE coach_trainee_links SET trainee_profile_id = ? WHERE coach_profile_id = ? AND trainee_profile_id = ?',
                [$realTraineeProfileId, $coachProfileId, $managedTraineeProfileId]
            );
            $conn->executeStatement(
                'UPDATE profiles SET merged_into_profile_id = ?, merged_at = ? WHERE id = ?',
                [$realTraineeProfileId, (new \DateTimeImmutable())->format('Y-m-d H:i:s'), $managedTraineeProfileId]
            );
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        $this->em->clear();
    }
}
