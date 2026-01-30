<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A person (alive, dead, undead, or fictional).
 *
 * @see https://schema.org/Person
 */
#[ApiResource(
    types: ['https://schema.org/Person'],
    operations: [
        new GetCollection(
            paginationClientItemsPerPage: true,
        ),
        new Get(),
    ],
    normalizationContext: [
        AbstractNormalizer::GROUPS => ['User:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
    ],
    security: 'is_granted("OIDC_SUPPORT_MANAGER") or is_granted("OIDC_ADMIN")'
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity('email')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    /**
     * @see https://schema.org/email
     */
    #[ORM\Column(unique: true)]
    #[Groups(groups: ['User:read'])]
    public string $email;

    /**
     * @see https://schema.org/givenName
     */
    #[ApiProperty(types: ['https://schema.org/givenName'])]
    #[Groups(groups: ['User:read'])]
    #[ORM\Column]
    public string $firstName;

    /**
     * @see https://schema.org/familyName
     */
    #[ApiProperty(types: ['https://schema.org/familyName'])]
    #[Groups(groups: ['User:read'])]
    #[ORM\Column]
    public string $lastName;

    public function getId(): int
    {
        return $this->id;
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see https://schema.org/name
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[Groups(groups: ['User:read'])]
    public function getName(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName));
    }
}
