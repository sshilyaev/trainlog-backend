<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CoachTraineeLinkRepository;
use App\Repository\ProfileRepository;

final class ProfileAccessChecker
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly CoachTraineeLinkRepository $coachTraineeLinkRepository,
    ) {
    }

    /**
     * User (Firebase UID) can access profile if they own it or are a linked coach of that trainee.
     */
    public function canAccess(string $profileId, string $firebaseUid): bool
    {
        $profile = $this->profileRepository->find($profileId);
        if ($profile === null) {
            return false;
        }
        if ($profile->getUserId() === $firebaseUid) {
            return true;
        }
        return $this->coachTraineeLinkRepository->existsByTraineeProfileIdAndCoachUserId($profileId, $firebaseUid);
    }
}
