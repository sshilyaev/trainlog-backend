<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ConnectionToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConnectionToken>
 */
final class ConnectionTokenRepository extends ServiceEntityRepository
{
    private const TOKEN_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0,O,1,I for readability
    private const TOKEN_LENGTH = 6;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConnectionToken::class);
    }

    public function findOneByToken(string $token): ?ConnectionToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findValidByToken(string $token): ?ConnectionToken
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('t')
            ->andWhere('t.token = :token')
            ->andWhere('t.used = false')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function generateUniqueToken(): string
    {
        $maxAttempts = 20;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $token = '';
            $len = strlen(self::TOKEN_CHARS);
            for ($j = 0; $j < self::TOKEN_LENGTH; $j++) {
                $token .= self::TOKEN_CHARS[random_int(0, $len - 1)];
            }
            if ($this->findOneByToken($token) === null) {
                return $token;
            }
        }
        throw new \RuntimeException('Could not generate unique connection token');
    }
}
