<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FirebaseIdTokenVerifier
{
    private const JWKS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    public function __construct(
        private readonly string $projectId,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array{sub: string, email?: string, email_verified?: bool} Decoded token claims; 'sub' is Firebase UID
     * @throws \InvalidArgumentException If token is missing, invalid or expired
     */
    public function verify(string $idToken): array
    {
        if ($this->projectId === '') {
            throw new \InvalidArgumentException('FIREBASE_PROJECT_ID не задан; проверка токенов невозможна');
        }
        $keys = $this->fetchPublicKeys();
        $publicKey = $this->getKeyForToken($idToken, $keys);
        $payload = JWT::decode($idToken, new Key($publicKey, 'RS256'));
        $payload = (array) $payload;

        $aud = $payload['aud'] ?? null;
        if ($aud !== $this->projectId) {
            throw new \InvalidArgumentException('Неверная аудитория токена');
        }
        $iss = $payload['iss'] ?? null;
        if ($iss !== 'https://securetoken.google.com/' . $this->projectId) {
            throw new \InvalidArgumentException('Неверный издатель токена');
        }
        $sub = $payload['sub'] ?? null;
        if (empty($sub)) {
            throw new \InvalidArgumentException('Неверный субъект токена');
        }

        return [
            'sub' => (string) $sub,
            'email' => $payload['email'] ?? null,
            'email_verified' => $payload['email_verified'] ?? null,
        ];
    }

    /**
     * @return array<string, string> kid => PEM public key
     */
    private function fetchPublicKeys(): array
    {
        $response = $this->httpClient->request('GET', self::JWKS_URL);
        $data = $response->toArray();
        $result = [];
        foreach ($data as $kid => $cert) {
            if ($kid === 'max-age' || $kid === 'age' || !\is_string($cert)) {
                continue;
            }
            $pem = str_contains($cert, 'BEGIN CERTIFICATE')
                ? $cert
                : "-----BEGIN CERTIFICATE-----\n" . chunk_split(str_replace("\n", '', $cert), 64, "\n") . "-----END CERTIFICATE-----";
            $pubKey = openssl_pkey_get_public($pem);
            if ($pubKey === false) {
                continue;
            }
            $pubKeyDetails = openssl_pkey_get_details($pubKey);
            $result[$kid] = $pubKeyDetails['key'] ?? $pem;
        }
        return $result;
    }

    private function getKeyForToken(string $idToken, array $keys): string
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Неверный формат токена');
        }
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/'), true), true);
        $kid = $header['kid'] ?? null;
        if (!$kid || !isset($keys[$kid])) {
            throw new \InvalidArgumentException('Неверный или неизвестный ключ токена');
        }
        return $keys[$kid];
    }
}
