<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\Profile;
use App\Enum\ApiError;
use App\Repository\ProfileRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProfileAppService
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly WeightSyncService $weightSyncService,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array{profiles: list<array<string, mixed>>} */
    public function list(string $userId): array
    {
        $profiles = $this->profileRepository->findByUserId($userId);
        return [
            'profiles' => array_map([$this, 'profileToArray'], $profiles),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(string $userId, array $data): array
    {
        $profile = (new Profile())
            ->setUserId($userId)
            ->setType((string) ($data['type'] ?? ''))
            ->setName((string) ($data['name'] ?? ''))
            ->setGymName(isset($data['gymName']) ? (string) $data['gymName'] : null)
            ->setGender(isset($data['gender']) ? (string) $data['gender'] : null)
            ->setIconEmoji(isset($data['iconEmoji']) ? (string) $data['iconEmoji'] : null)
            ->setOwnerCoachProfileId(isset($data['ownerCoachProfileId']) ? (string) $data['ownerCoachProfileId'] : null);

        if (isset($data['dateOfBirth']) && $data['dateOfBirth'] !== null && $data['dateOfBirth'] !== '') {
            try {
                $profile->setDateOfBirth(new \DateTimeImmutable((string) $data['dateOfBirth']));
            } catch (\Throwable) {
                // ignore invalid date
            }
        }
        if (array_key_exists('phoneNumber', $data)) {
            $profile->setPhoneNumber($data['phoneNumber'] === null || $data['phoneNumber'] === '' ? null : (string) $data['phoneNumber']);
        }
        if (array_key_exists('telegramUsername', $data)) {
            $profile->setTelegramUsername($data['telegramUsername'] === null || $data['telegramUsername'] === '' ? null : (string) $data['telegramUsername']);
        }
        if (array_key_exists('notes', $data)) {
            $profile->setNotes($data['notes'] === null || $data['notes'] === '' ? null : (string) $data['notes']);
        }
        if (array_key_exists('height', $data) && $data['height'] !== null && $data['height'] !== '') {
            $profile->setHeight((float) $data['height']);
        }
        if (array_key_exists('weight', $data) && $data['weight'] !== null && $data['weight'] !== '') {
            $this->weightSyncService->setCurrentWeight($profile, (float) $data['weight'], new \DateTimeImmutable('today'));
        }

        $errors = $this->validator->validate($profile);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }

        $this->em->persist($profile);
        $this->em->flush();

        return $this->profileToArray($profile);
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $userId): array
    {
        $profile = $this->profileRepository->findOneByIdAndUserId($id, $userId);
        if ($profile === null) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        return $this->profileToArray($profile);
    }

    /**
     * Return profile arrays for given IDs, only those the user can access (own or linked).
     *
     * @param list<string> $ids
     * @return list<array<string, mixed>>
     */
    public function getManyByIds(array $ids, string $userId): array
    {
        if ($ids === []) {
            return [];
        }
        $profiles = $this->profileRepository->findByIds($ids);
        $result = [];
        foreach ($profiles as $profile) {
            if ($this->profileAccessChecker->canAccess($profile->getId(), $userId)) {
                $result[] = $this->profileToArray($profile);
            }
        }
        return $result;
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $userId, array $data): array
    {
        $profile = $this->profileRepository->find($id);
        if (!$profile instanceof Profile) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $isOwner = $profile->getUserId() === $userId;
        $canManageWeight = false;
        if (!$isOwner) {
            $coachProfileIds = $this->profileRepository->findCoachProfileIdsByUserId($userId);
            $canManageWeight = $profile->getType() === Profile::TYPE_TRAINEE
                && $profile->getOwnerCoachProfileId() !== null
                && in_array($profile->getOwnerCoachProfileId(), $coachProfileIds, true);
            if (!$canManageWeight) {
                throw new ApiException(ApiError::ProfileNotFound);
            }
        }
        if (!$isOwner) {
            $nonWeightFields = $data;
            unset($nonWeightFields['weight']);
            foreach ($nonWeightFields as $value) {
                if ($value !== null && $value !== '') {
                    throw new ApiException(ApiError::ProfileNotFound);
                }
            }
        }
        if (isset($data['name'])) {
            $profile->setName((string) $data['name']);
        }
        if (array_key_exists('gymName', $data)) {
            $profile->setGymName($data['gymName'] === null ? null : (string) $data['gymName']);
        }
        if (array_key_exists('gender', $data)) {
            $profile->setGender($data['gender'] === null ? null : (string) $data['gender']);
        }
        if (array_key_exists('iconEmoji', $data)) {
            $profile->setIconEmoji($data['iconEmoji'] === null ? null : (string) $data['iconEmoji']);
        }
        if (array_key_exists('developerMode', $data) && $data['developerMode'] !== null) {
            $profile->setDeveloperMode((bool) $data['developerMode']);
        }
        if (array_key_exists('dateOfBirth', $data)) {
            if ($data['dateOfBirth'] === null || $data['dateOfBirth'] === '') {
                $profile->setDateOfBirth(null);
            } else {
                try {
                    $profile->setDateOfBirth(new \DateTimeImmutable((string) $data['dateOfBirth']));
                } catch (\Throwable) {
                    // ignore
                }
            }
        }
        if (array_key_exists('phoneNumber', $data)) {
            $profile->setPhoneNumber($data['phoneNumber'] === null || $data['phoneNumber'] === '' ? null : (string) $data['phoneNumber']);
        }
        if (array_key_exists('telegramUsername', $data)) {
            $profile->setTelegramUsername($data['telegramUsername'] === null || $data['telegramUsername'] === '' ? null : (string) $data['telegramUsername']);
        }
        if (array_key_exists('notes', $data)) {
            $profile->setNotes($data['notes'] === null || $data['notes'] === '' ? null : (string) $data['notes']);
        }
        if (array_key_exists('height', $data)) {
            $profile->setHeight($data['height'] === null || $data['height'] === '' ? null : (float) $data['height']);
        }
        if (array_key_exists('weight', $data) && $data['weight'] !== null && $data['weight'] !== '') {
            $this->weightSyncService->setCurrentWeight($profile, (float) $data['weight'], new \DateTimeImmutable('today'));
        }

        $errors = $this->validator->validate($profile);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }

        $this->em->flush();
        return $this->profileToArray($profile);
    }

    public function delete(string $id, string $userId): void
    {
        $profile = $this->profileRepository->findOneByIdAndUserId($id, $userId);
        if ($profile === null) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $this->em->remove($profile);
        $this->em->flush();
    }

    /** @return array<string, mixed> */
    private function profileToArray(Profile $p): array
    {
        $result = [
            'id' => $p->getId(),
            'userId' => $p->getUserId(),
            'type' => $p->getType(),
            'name' => $p->getName(),
            'gymName' => $p->getGymName(),
            'gender' => $p->getGender(),
            'iconEmoji' => $p->getIconEmoji(),
            'createdAt' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'developerMode' => $p->isDeveloperMode(),
            'dateOfBirth' => $p->getDateOfBirth()?->format('Y-m-d'),
            'phoneNumber' => $p->getPhoneNumber(),
            'telegramUsername' => $p->getTelegramUsername(),
            'notes' => $p->getNotes(),
            'height' => $p->getHeight(),
            'weight' => $p->getWeight(),
            'weightUpdatedAt' => $p->getWeightUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'ownerCoachProfileId' => $p->getOwnerCoachProfileId(),
            'mergedIntoProfileId' => $p->getMergedIntoProfileId(),
        ];
        return $result;
    }
}
