<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\GuestAccessToken;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class GuestUser implements UserInterface
{
    public function __construct(private GuestAccessToken $token)
    {
    }

    public function getRoles(): array
    {
        return ['ROLE_GUEST'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return 'guest:' . ($this->token->getId() ?? 'unknown');
    }
}
