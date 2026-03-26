<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\CoachTraineeLink;
use App\Enum\ApiError;
use App\Repository\CoachTraineeLinkRepository;
use App\Repository\ProfileRepository;
use App\Service\Api\ProfileAppService;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CoachTraineeLinkAppService
{
    public function __construct(
        private readonly CoachTraineeLinkRepository $linkRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly ProfileAppService $profileAppService,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @return array{links: list<array<string, mixed>>, profiles?: list<array<string, mixed>>}
     */
    public function list(string $profileId, string $as, string $userId, bool $embedProfiles = false): array
    {
        if ($profileId === '') {
            throw new ApiException(ApiError::ProfileIdQueryRequired);
        }
        if ($as !== 'coach' && $as !== 'trainee') {
            throw new ApiException(ApiError::AsMustBeCoachOrTrainee);
        }
        if (!$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $links = $as === 'coach'
            ? $this->linkRepository->findByCoachProfileId($profileId)
            : $this->linkRepository->findByTraineeProfileId($profileId);
        $result = ['links' => array_map([$this, 'linkToArray'], $links)];
        if ($embedProfiles && $links !== []) {
            $profileIds = array_values(array_unique(array_map(
                fn (CoachTraineeLink $link) => $as === 'coach' ? $link->getTraineeProfile()->getId() : $link->getCoachProfile()->getId(),
                $links
            )));
            $result['profiles'] = $this->profileAppService->getManyByIds($profileIds, $userId);
        }
        return $result;
    }

    /**
     * Create a coach–trainee link (without token). Caller must own the coach profile and have access to the trainee profile.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(string $userId, array $data): array
    {
        $coachProfileId = $data['coachProfileId'] ?? null;
        $traineeProfileId = $data['traineeProfileId'] ?? null;
        if ($coachProfileId === null || $coachProfileId === '' || $traineeProfileId === null || $traineeProfileId === '') {
            throw new ApiException(ApiError::CoachAndTraineeProfileIdRequired);
        }
        $coachProfile = $this->profileRepository->find($coachProfileId);
        if ($coachProfile === null || $coachProfile->getType() !== 'coach') {
            throw new ApiException(ApiError::CoachProfileNotFound);
        }
        if ($coachProfile->getUserId() !== $userId) {
            throw new ApiException(ApiError::CoachProfileNotFound);
        }
        if (!$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
            throw new ApiException(ApiError::TraineeProfileNotFound);
        }
        $traineeProfile = $this->profileRepository->find($traineeProfileId);
        if ($traineeProfile === null || $traineeProfile->getType() !== 'trainee') {
            throw new ApiException(ApiError::TraineeProfileNotFound);
        }
        if ($this->linkRepository->existsLink($coachProfileId, $traineeProfileId)) {
            throw new ApiException(ApiError::TraineeAlreadyLinked);
        }
        $link = (new CoachTraineeLink())
            ->setCoachProfile($coachProfile)
            ->setTraineeProfile($traineeProfile);
        if (array_key_exists('displayName', $data) && $data['displayName'] !== null) {
            $link->setDisplayName((string) $data['displayName']);
        }
        $this->em->persist($link);
        $this->em->flush();
        return $this->linkToArray($link);
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $userId): array
    {
        $link = $this->linkRepository->findOneById($id);
        if ($link === null || !$this->canAccessLink($link, $userId)) {
            throw new ApiException(ApiError::LinkNotFound);
        }
        return $this->linkToArray($link);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $userId, array $data): array
    {
        $link = $this->linkRepository->findOneById($id);
        if ($link === null) {
            throw new ApiException(ApiError::LinkNotFound);
        }
        if ($link->getCoachProfile()->getUserId() !== $userId) {
            throw new ApiException(ApiError::OnlyCoachCanUpdateLink);
        }
        if (array_key_exists('displayName', $data)) {
            $link->setDisplayName($data['displayName'] === null ? null : (string) $data['displayName']);
        }
        if (array_key_exists('note', $data)) {
            $link->setNote($data['note'] === null ? null : (string) $data['note']);
        }
        if (array_key_exists('archived', $data)) {
            $link->setArchived((bool) $data['archived']);
        }
        $errors = $this->validator->validate($link);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }
        $this->em->flush();
        return $this->linkToArray($link);
    }

    public function delete(string $id, string $userId): void
    {
        $link = $this->linkRepository->findOneById($id);
        if ($link === null || !$this->canAccessLink($link, $userId)) {
            throw new ApiException(ApiError::LinkNotFound);
        }
        $this->em->remove($link);
        $this->em->flush();
    }

    private function canAccessLink(CoachTraineeLink $link, string $userId): bool
    {
        return $link->getCoachProfile()->getUserId() === $userId
            || $link->getTraineeProfile()->getUserId() === $userId;
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
            'archived' => $link->isArchived(),
            'createdAt' => $link->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
