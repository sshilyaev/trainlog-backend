<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\User;
use App\Enum\ApiError;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth', name: 'api_v1_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly JwtService $jwtService,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Укажите корректный email', 'code' => 'invalid_email'], 400);
        }
        if (strlen($password) < 6) {
            return new JsonResponse(['error' => 'Пароль должен быть не менее 6 символов', 'code' => 'password_too_short'], 400);
        }
        if ($this->userRepository->emailExists($email)) {
            return new JsonResponse(['error' => 'Пользователь с таким email уже существует', 'code' => 'email_taken'], 409);
        }

        $user = new User($email, password_hash($password, PASSWORD_BCRYPT));
        $this->userRepository->save($user);

        return new JsonResponse($this->tokenResponse($user), Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return new JsonResponse(['error' => 'Укажите email и пароль', 'code' => 'missing_credentials'], 400);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user === null || !password_verify($password, $user->getPasswordHash())) {
            return new JsonResponse(['error' => 'Неверный email или пароль', 'code' => 'invalid_credentials'], 401);
        }

        return new JsonResponse($this->tokenResponse($user));
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $refreshToken = trim((string) ($data['refreshToken'] ?? ''));
        if ($refreshToken === '') {
            return new JsonResponse(['error' => 'Укажите refreshToken', 'code' => 'missing_refresh_token'], 400);
        }

        try {
            $claims = $this->jwtService->verify($refreshToken, 'refresh');
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => ApiError::InvalidOrExpiredToken->value], 401);
        }

        $user = $this->userRepository->find($claims['sub']);
        if ($user === null) {
            return new JsonResponse(['error' => 'Пользователь не найден', 'code' => 'user_not_found'], 401);
        }

        return new JsonResponse($this->tokenResponse($user));
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $auth = $request->headers->get('Authorization', '');
        if (!str_starts_with($auth, 'Bearer ')) {
            return ApiResponse::error(ApiError::MissingOrInvalidAuthHeader);
        }
        try {
            $claims = $this->jwtService->verify(trim(substr($auth, 7)), 'access');
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => ApiError::InvalidOrExpiredToken->value], 401);
        }
        $user = $this->userRepository->find($claims['sub']);
        if ($user === null) {
            return new JsonResponse(['error' => 'Пользователь не найден', 'code' => 'user_not_found'], 401);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    /** @return array<string, string> */
    private function tokenResponse(User $user): array
    {
        return [
            'accessToken' => $this->jwtService->issueAccessToken($user->getId()),
            'refreshToken' => $this->jwtService->issueRefreshToken($user->getId()),
            'userId' => $user->getId(),
        ];
    }
}
