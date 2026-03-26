<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * HTTP Basic auth для /admin. Пароль читается из getenv('ADMIN_PASSWORD') при каждом запросе.
 */
final class AdminHttpBasicAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const ADMIN_USERNAME = 'admin';

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $response = new Response();
        $response->headers->set('WWW-Authenticate', 'Basic realm="Admin"');
        $response->setStatusCode(401);
        return $response;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('PHP_AUTH_USER');
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->headers->get('PHP_AUTH_USER', '');
        $password = $request->headers->get('PHP_AUTH_PW', '');

        $expectedPassword = trim((string) (
            getenv('ADMIN_PASSWORD') ?:
            $_ENV['ADMIN_PASSWORD'] ??
            $_SERVER['ADMIN_PASSWORD'] ??
            ''
        ));

        if ($username !== self::ADMIN_USERNAME || !hash_equals($expectedPassword, $password)) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        $user = new InMemoryUser(self::ADMIN_USERNAME, '', ['ROLE_ADMIN']);
        return new SelfValidatingPassport(new UserBadge(self::ADMIN_USERNAME, static fn () => $user));
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();
        return new UsernamePasswordToken($user, $firewallName, $user->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->start($request, $exception);
    }
}
