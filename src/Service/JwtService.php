<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Minimal HS256 JWT without external dependencies.
 * Secret is read from JWT_SECRET env var.
 */
final class JwtService
{
    private const ALG = 'HS256';
    /** Access token TTL: 1 hour */
    private const ACCESS_TTL = 3600;
    /** Refresh token TTL: 30 days */
    private const REFRESH_TTL = 2592000;

    public function __construct(private readonly string $secret)
    {
        if ($secret === '') {
            throw new \RuntimeException('JWT_SECRET env var is not set');
        }
    }

    public function issueAccessToken(string $userId): string
    {
        return $this->encode(['sub' => $userId, 'type' => 'access'], self::ACCESS_TTL);
    }

    public function issueRefreshToken(string $userId): string
    {
        return $this->encode(['sub' => $userId, 'type' => 'refresh'], self::REFRESH_TTL);
    }

    /**
     * Verify and decode a token.
     * @return array{sub: string, type: string}
     * @throws \InvalidArgumentException on invalid/expired token
     */
    public function verify(string $token, string $expectedType = 'access'): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Неверный формат токена');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $expected = $this->sign($headerB64 . '.' . $payloadB64);
        if (!hash_equals($expected, $sigB64)) {
            throw new \InvalidArgumentException('Подпись токена недействительна');
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Неверный payload токена');
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw new \InvalidArgumentException('Токен истёк');
        }
        if (($payload['type'] ?? '') !== $expectedType) {
            throw new \InvalidArgumentException('Неверный тип токена');
        }
        if (empty($payload['sub'])) {
            throw new \InvalidArgumentException('Токен не содержит sub');
        }

        return ['sub' => (string) $payload['sub'], 'type' => (string) $payload['type']];
    }

    private function encode(array $extra, int $ttl): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => self::ALG, 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode(array_merge($extra, [
            'iat' => time(),
            'exp' => time() + $ttl,
        ])));
        $sig = $this->sign($header . '.' . $payload);
        return $header . '.' . $payload . '.' . $sig;
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
