<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\Event;
use App\Enum\ApiError;
use App\Repository\EventRepository;
use App\Repository\ProfileRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class EventAppService
{
    private const IDEMPOTENCY_TTL_SECONDS = 86400; // 24 hours

    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly EntityManagerInterface $em,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{events: list<array<string, mixed>>, nextCursor?: string|null}
     */
    public function list(?string $coachProfileId, ?string $traineeProfileId, ?string $profileId, string $userId, ?int $limit = null, ?string $after = null): array
    {
        if ($profileId !== null && $profileId !== '') {
            if (!$this->profileAccessChecker->canAccess($profileId, $userId)) {
                throw new ApiException(ApiError::ProfileNotFound);
            }
            if ($limit !== null && $limit > 0) {
                [$events, $nextCursor] = $this->eventRepository->findPaginated(null, null, $profileId, min($limit, 200), $after);
                return [
                    'events' => array_map([$this, 'eventToArray'], $events),
                    'nextCursor' => $nextCursor,
                ];
            }
            $events = $this->eventRepository->findByProfileId($profileId);
            return ['events' => array_map([$this, 'eventToArray'], $events)];
        }

        if ($coachProfileId === null || $coachProfileId === '' || $traineeProfileId === null || $traineeProfileId === '') {
            throw new ApiException(ApiError::CoachAndTraineeProfileIdRequired);
        }

        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId) || !$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }

        if ($limit !== null && $limit > 0) {
            [$events, $nextCursor] = $this->eventRepository->findPaginated($coachProfileId, $traineeProfileId, null, min($limit, 200), $after);
            return [
                'events' => array_map([$this, 'eventToArray'], $events),
                'nextCursor' => $nextCursor,
            ];
        }

        $events = $this->eventRepository->findByCoachAndTrainee($coachProfileId, $traineeProfileId);
        return ['events' => array_map([$this, 'eventToArray'], $events)];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed> event array
     */
    public function create(string $userId, array $data): array
    {
        $idempotencyKey = isset($data['idempotencyKey']) && (string) $data['idempotencyKey'] !== ''
            ? (string) $data['idempotencyKey']
            : null;

        if ($idempotencyKey !== null) {
            $cacheKey = 'idempotency_event_' . $userId . '_' . md5($idempotencyKey);
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($userId, $data) {
                $item->expiresAfter(self::IDEMPOTENCY_TTL_SECONDS);
                return $this->performCreateEvent($userId, $data);
            });
        }

        return $this->performCreateEvent($userId, $data);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed> */
    private function performCreateEvent(string $userId, array $data): array
    {
        $coachProfileId = $data['coachProfileId'] ?? null;
        $traineeProfileId = $data['traineeProfileId'] ?? null;
        if ($coachProfileId === null || $coachProfileId === '' || $traineeProfileId === null || $traineeProfileId === '') {
            throw new ApiException(ApiError::CoachAndTraineeProfileIdRequired);
        }
        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId) || !$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $coachProfile = $this->profileRepository->find($coachProfileId);
        $traineeProfile = $this->profileRepository->find($traineeProfileId);
        if ($coachProfile === null || $traineeProfile === null) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $dateStr = $data['date'] ?? '';
        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Throwable) {
            throw new ApiException(ApiError::InvalidDateFormat);
        }
        $event = (new Event())
            ->setCoachProfile($coachProfile)
            ->setTraineeProfile($traineeProfile)
            ->setTitle((string) ($data['title'] ?? ''))
            ->setDate($date)
            ->setDescription(isset($data['description']) ? (string) $data['description'] : null)
            ->setRemind((bool) ($data['remind'] ?? false))
            ->setColorHex(isset($data['colorHex']) ? (string) $data['colorHex'] : null)
            ->setEventType(isset($data['eventType']) ? (string) $data['eventType'] : Event::TYPE_GENERAL);
        $this->em->persist($event);
        $this->em->flush();
        return $this->eventToArray($event);
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $userId): array
    {
        $event = $this->eventRepository->findOneById($id);
        if ($event === null || !$this->canAccessEvent($event, $userId)) {
            throw new ApiException(ApiError::EventNotFound);
        }
        return $this->eventToArray($event);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $userId, array $data): array
    {
        $event = $this->eventRepository->findOneById($id);
        if ($event === null || !$this->canAccessEvent($event, $userId)) {
            throw new ApiException(ApiError::EventNotFound);
        }
        if (isset($data['title'])) {
            $event->setTitle((string) $data['title']);
        }
        if (isset($data['date'])) {
            try {
                $event->setDate(new \DateTimeImmutable((string) $data['date']));
            } catch (\Throwable) {
                throw new ApiException(ApiError::InvalidDateFormat);
            }
        }
        if (array_key_exists('description', $data)) {
            $event->setDescription($data['description'] === null ? null : (string) $data['description']);
        }
        if (array_key_exists('remind', $data)) {
            $event->setRemind((bool) $data['remind']);
        }
        if (array_key_exists('colorHex', $data)) {
            $event->setColorHex($data['colorHex'] === null ? null : (string) $data['colorHex']);
        }
        if (array_key_exists('eventType', $data)) {
            $event->setEventType((string) $data['eventType']);
        }
        if (array_key_exists('isCancelled', $data)) {
            $event->setIsCancelled((bool) $data['isCancelled']);
        }
        $this->em->flush();
        return $this->eventToArray($event);
    }

    public function delete(string $id, string $userId): void
    {
        $event = $this->eventRepository->findOneById($id);
        if ($event === null || !$this->canAccessEvent($event, $userId)) {
            throw new ApiException(ApiError::EventNotFound);
        }
        $this->em->remove($event);
        $this->em->flush();
    }

    private function canAccessEvent(Event $event, string $userId): bool
    {
        return $this->profileAccessChecker->canAccess($event->getCoachProfile()->getId(), $userId)
            && $this->profileAccessChecker->canAccess($event->getTraineeProfile()->getId(), $userId);
    }

    /** @return array<string, mixed> */
    public function eventToArrayPublic(Event $e): array
    {
        return $this->eventToArray($e);
    }

    /** @return array<string, mixed> */
    private function eventToArray(Event $e): array
    {
        return [
            'id' => $e->getId(),
            'coachProfileId' => $e->getCoachProfile()->getId(),
            'traineeProfileId' => $e->getTraineeProfile()->getId(),
            'title' => $e->getTitle(),
            'date' => $e->getDate()->format('Y-m-d'),
            'eventDescription' => $e->getDescription(),
            'remind' => $e->isRemind(),
            'colorHex' => $e->getColorHex(),
            'eventType' => $e->getEventType(),
            'isCancelled' => $e->isCancelled(),
            'createdAt' => $e->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
