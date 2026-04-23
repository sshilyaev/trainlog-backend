<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\Event;
use App\Repository\MembershipRepository;
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
        private readonly MembershipRepository $membershipRepository,
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
        [$mode, $date, $periodStart, $periodEnd, $periodType, $freezeMembership] = $this->resolveEventPeriodData($data);
        $event = (new Event())
            ->setCoachProfile($coachProfile)
            ->setTraineeProfile($traineeProfile)
            ->setTitle((string) ($data['title'] ?? ''))
            ->setDate($date)
            ->setMode($mode)
            ->setPeriodStart($periodStart)
            ->setPeriodEnd($periodEnd)
            ->setPeriodType($periodType)
            ->setFreezeMembership($freezeMembership)
            ->setDescription(isset($data['description']) ? (string) $data['description'] : null)
            ->setRemind((bool) ($data['remind'] ?? false))
            ->setColorHex(isset($data['colorHex']) ? (string) $data['colorHex'] : null)
            ->setEventType(isset($data['eventType']) ? (string) $data['eventType'] : Event::TYPE_GENERAL);
        $this->em->persist($event);
        $this->em->flush();
        $this->syncFreezeDaysForPair($coachProfileId, $traineeProfileId);
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
        if (
            array_key_exists('date', $data)
            || array_key_exists('mode', $data)
            || array_key_exists('periodStart', $data)
            || array_key_exists('periodEnd', $data)
            || array_key_exists('periodType', $data)
            || array_key_exists('freezeMembership', $data)
        ) {
            $merged = [
                'mode' => $data['mode'] ?? $event->getMode(),
                'date' => $data['date'] ?? $event->getDate()->format('Y-m-d'),
                'periodStart' => $data['periodStart'] ?? ($event->getPeriodStart()?->format('Y-m-d')),
                'periodEnd' => $data['periodEnd'] ?? ($event->getPeriodEnd()?->format('Y-m-d')),
                'periodType' => array_key_exists('periodType', $data) ? $data['periodType'] : $event->getPeriodType(),
                'freezeMembership' => array_key_exists('freezeMembership', $data) ? (bool) $data['freezeMembership'] : $event->isFreezeMembership(),
            ];
            [$mode, $date, $periodStart, $periodEnd, $periodType, $freezeMembership] = $this->resolveEventPeriodData($merged);
            $event
                ->setMode($mode)
                ->setDate($date)
                ->setPeriodStart($periodStart)
                ->setPeriodEnd($periodEnd)
                ->setPeriodType($periodType)
                ->setFreezeMembership($freezeMembership);
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
        $this->syncFreezeDaysForPair(
            $event->getCoachProfile()->getId(),
            $event->getTraineeProfile()->getId()
        );
        return $this->eventToArray($event);
    }

    public function delete(string $id, string $userId): void
    {
        $event = $this->eventRepository->findOneById($id);
        if ($event === null || !$this->canAccessEvent($event, $userId)) {
            throw new ApiException(ApiError::EventNotFound);
        }
        $coachProfileId = $event->getCoachProfile()->getId();
        $traineeProfileId = $event->getTraineeProfile()->getId();
        $this->em->remove($event);
        $this->em->flush();
        $this->syncFreezeDaysForPair($coachProfileId, $traineeProfileId);
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
        $periodStart = $e->getPeriodStart() ?? $e->getDate();
        $periodEnd = $e->getPeriodEnd() ?? $e->getDate();
        return [
            'id' => $e->getId(),
            'coachProfileId' => $e->getCoachProfile()->getId(),
            'traineeProfileId' => $e->getTraineeProfile()->getId(),
            'title' => $e->getTitle(),
            'date' => $periodStart->format('Y-m-d'),
            'mode' => $e->getMode(),
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->format('Y-m-d'),
            'periodType' => $e->getPeriodType(),
            'freezeMembership' => $e->isFreezeMembership(),
            'eventDescription' => $e->getDescription(),
            'remind' => $e->isRemind(),
            'colorHex' => $e->getColorHex(),
            'eventType' => $e->getEventType(),
            'isCancelled' => $e->isCancelled(),
            'createdAt' => $e->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0:string,1:\DateTimeImmutable,2:\DateTimeImmutable,3:\DateTimeImmutable,4:?string,5:bool}
     */
    private function resolveEventPeriodData(array $data): array
    {
        $mode = (string) ($data['mode'] ?? Event::MODE_DATE);
        if ($mode !== Event::MODE_DATE && $mode !== Event::MODE_PERIOD) {
            throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Некорректный mode для события']]);
        }

        $parseDate = static function (?string $value, string $errorText): \DateTimeImmutable {
            try {
                return new \DateTimeImmutable((string) $value);
            } catch (\Throwable) {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => [$errorText]]);
            }
        };

        if ($mode === Event::MODE_PERIOD) {
            $periodStartRaw = $data['periodStart'] ?? null;
            $periodEndRaw = $data['periodEnd'] ?? null;
            if ($periodStartRaw === null || $periodEndRaw === null) {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Для периода обязательны periodStart и periodEnd']]);
            }
            $periodStart = $parseDate((string) $periodStartRaw, 'Некорректный формат periodStart');
            $periodEnd = $parseDate((string) $periodEndRaw, 'Некорректный формат periodEnd');
            if ($periodStart > $periodEnd) {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['periodStart не может быть позже periodEnd']]);
            }
            $periodType = isset($data['periodType']) && $data['periodType'] !== '' ? (string) $data['periodType'] : null;
            if ($periodType !== null && !in_array($periodType, [Event::TYPE_VACATION, Event::TYPE_SICK], true)) {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['periodType должен быть vacation или sick']]);
            }
            $freezeMembership = (bool) ($data['freezeMembership'] ?? false);
            return [Event::MODE_PERIOD, $periodStart, $periodStart, $periodEnd, $periodType, $freezeMembership];
        }

        $dateStr = $data['date'] ?? '';
        $date = $parseDate((string) $dateStr, ApiError::InvalidDateFormat->value);
        return [Event::MODE_DATE, $date, $date, $date, null, false];
    }

    private function syncFreezeDaysForPair(string $coachProfileId, string $traineeProfileId): void
    {
        $membership = $this->membershipRepository->findLatestActiveUnlimitedForPair($coachProfileId, $traineeProfileId);
        if ($membership === null) {
            return;
        }

        $events = $this->eventRepository->findByCoachAndTrainee($coachProfileId, $traineeProfileId);
        $totalFreezeDays = 0;
        foreach ($events as $event) {
            if (
                $event->isCancelled()
                || $event->getMode() !== Event::MODE_PERIOD
                || !$event->isFreezeMembership()
            ) {
                continue;
            }

            $start = $event->getPeriodStart() ?? $event->getDate();
            $end = $event->getPeriodEnd() ?? $event->getDate();
            if ($end < $start) {
                continue;
            }

            $days = (int) $start->diff($end)->format('%a') + 1;
            $totalFreezeDays += max(0, $days);
        }

        if ($membership->getFreezeDays() !== $totalFreezeDays) {
            $membership->setFreezeDays($totalFreezeDays);
            $this->em->flush();
        }
    }
}
