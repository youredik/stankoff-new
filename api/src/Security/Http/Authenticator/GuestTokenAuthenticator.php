<?php

declare(strict_types=1);

namespace App\Security\Http\Authenticator;

use App\Entity\GuestAccessToken;
use App\Repository\GuestAccessTokenRepository;
use App\Security\GuestUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class GuestTokenAuthenticator extends AbstractAuthenticator
{
    private const HEADER_NAME = 'X-Guest-Token';
    private const QUERY_PARAM = 'guest_token';

    public function __construct(
        private readonly GuestAccessTokenRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($request->headers->has('Authorization')) {
            return false;
        }

        return $this->extractToken($request) !== null;
    }

    public function authenticate(Request $request): Passport
    {
        $tokenValue = $this->extractToken($request);
        if (!$tokenValue) {
            throw new CustomUserMessageAuthenticationException('Guest token missing.');
        }

        $token = $this->repository->findValidByToken($tokenValue);
        if (!$token) {
            throw new CustomUserMessageAuthenticationException('Invalid guest token.');
        }

        if (!$token->allowsScope(GuestAccessToken::SCOPE_SUPPORT_TICKETS_READ)) {
            throw new CustomUserMessageAuthenticationException('Guest token scope not allowed.');
        }

        $token->markUsed();
        $this->entityManager->flush();

        return new SelfValidatingPassport(
            new UserBadge(
                'guest:' . ($token->getId() ?? 'unknown'),
                static fn (): GuestUser => new GuestUser($token),
            ),
        );
    }

    public function onAuthenticationSuccess(Request $request, \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token, string $firewallName): ?\Symfony\Component\HttpFoundation\Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?\Symfony\Component\HttpFoundation\Response
    {
        return new JsonResponse(['message' => $exception->getMessageKey()], 401);
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->headers->get(self::HEADER_NAME);
        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $queryToken = $request->query->get(self::QUERY_PARAM);
        if (is_string($queryToken) && $queryToken !== '') {
            return $queryToken;
        }

        return null;
    }
}
