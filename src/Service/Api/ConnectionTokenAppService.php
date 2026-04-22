<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Entity\CoachTraineeLink;
use App\Entity\ConnectionToken;
use App\Repository\ConnectionTokenRepository;
use App\Repository\CoachTraineeLinkRepository;
use App\Repository\ProfileRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;

final class ConnectionTokenAppService
{
    private const TOKEN_TTL_MINUTES = 15;

    public function __construct(
        private readonly ConnectionTokenRepository $connectionTokenRepository,
        private readonly CoachTraineeLinkRepository $coachTraineeLinkRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array{token: string, expiresAt: string} */
    public function create(string $userId, array $data): array
    {
        $traineeProfileId = $data['traineeProfileId'] ?? null;
        if ($traineeProfileId === null || $traineeProfileId === '') {
            throw new ApiException(ApiError::TraineeProfileIdRequired);
        }
        if (!$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $profile = $this->profileRepository->find($traineeProfileId);
        if ($profile === null || $profile->getType() !== 'trainee') {
            throw new ApiException(ApiError::ProfileMustBeTrainee);
        }
        $tokenString = $this->connectionTokenRepository->generateUniqueToken();
        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+' . self::TOKEN_TTL_MINUTES . ' minutes');
        $token = (new ConnectionToken())
            ->setTraineeProfile($profile)
            ->setToken($tokenString)
            ->setExpiresAt($expiresAt);
        $this->em->persist($token);
        $this->em->flush();
        return [
            'token' => $tokenString,
            'expiresAt' => $expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function use(string $userId, array $data): array
    {
        $tokenString = $data['token'] ?? null;
        $coachProfileId = $data['coachProfileId'] ?? null;
        if ($tokenString === null || $tokenString === '' || $coachProfileId === null || $coachProfileId === '') {
            throw new ApiException(ApiError::TokenAndCoachProfileIdRequired);
        }
        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)) {
            throw new ApiException(ApiError::CoachProfileNotFound);
        }
        $coachProfile = $this->profileRepository->find($coachProfileId);
        if ($coachProfile === null || $coachProfile->getType() !== 'coach') {
            throw new ApiException(ApiError::ProfileMustBeCoach);
        }
        $connectionToken = $this->connectionTokenRepository->findValidByToken(trim((string) $tokenString));
        if ($connectionToken === null) {
            throw new ApiException(ApiError::ConnectionTokenInvalidOrUsed);
        }
        $traineeProfile = $connectionToken->getTraineeProfile();
        if ($this->coachTraineeLinkRepository->existsLink($coachProfileId, $traineeProfile->getId())) {
            $connectionToken->setUsed(true);
            $this->em->flush();
            throw new ApiException(ApiError::TraineeAlreadyLinked);
        }
        $link = (new CoachTraineeLink())
            ->setCoachProfile($coachProfile)
            ->setTraineeProfile($traineeProfile);
        $connectionToken->setUsed(true);
        $this->em->persist($link);
        $this->em->flush();
        return $this->linkToArray($link);
    }

    /** @return array{traineeProfileId: string, traineeName: string} */
    public function preview(string $tokenString): array
    {
        if ($tokenString === '') {
            throw new ApiException(ApiError::TokenQueryRequired);
        }
        $connectionToken = $this->connectionTokenRepository->findValidByToken($tokenString);
        if ($connectionToken === null) {
            throw new ApiException(ApiError::ConnectionTokenInvalidExpired);
        }
        $trainee = $connectionToken->getTraineeProfile();
        return [
            'traineeProfileId' => $trainee->getId(),
            'traineeName' => $trainee->getName(),
        ];
    }

    /** @return array<string, mixed> */
    private function linkToArray(CoachTraineeLink $link): array
    {
        return [
            'id' => $link->getId(),
            'coachProfileId' => $link->getCoachProfile()->getId(),
            'traineeProfileId' => $link->getTraineeProfile()->getId(),
            'displayName' => $link->getDisplayName(),
            'note' => $link->getNote(),
            'favorite' => $link->isFavorite(),
            'createdAt' => $link->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
