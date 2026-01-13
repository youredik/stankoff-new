<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Enum\SupportTicketStatus;
use App\Repository\SupportTicketRepository;
use App\State\Processor\SupportTicketCreateProcessor;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            paginationClientItemsPerPage: true,
            order: ['createdAt' => 'DESC'],
        ),
        new Post(
            processor: SupportTicketCreateProcessor::class,
        ),
        new Get(),
    ],
    normalizationContext: [
        AbstractNormalizer::GROUPS => ['SupportTicket:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
    ],
    denormalizationContext: [
        AbstractNormalizer::GROUPS => ['SupportTicket:write'],
    ],
    collectDenormalizationErrors: true,
    security: 'is_granted("OIDC_ADMIN")'
)]
#[ORM\Entity(repositoryClass: SupportTicketRepository::class)]
class SupportTicket
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[ApiProperty(example: 'Проблема с запуском станка', iris: ['https://schema.org/name'])]
    #[Assert\NotBlank(allowNull: false)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ApiFilter(OrderFilter::class)]
    #[ApiFilter(SearchFilter::class, strategy: 'i' . SearchFilterInterface::STRATEGY_PARTIAL)]
    #[Groups(groups: ['SupportTicket:read', 'SupportTicket:write',])]
    public string $subject;

    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['SupportTicket:read', 'SupportTicket:write',])]
    #[ORM\Column(type: Types::TEXT)]
    public string $description;

    #[ApiProperty(example: 'Иван Иванов')]
    #[Assert\NotBlank(allowNull: false)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(groups: ['SupportTicket:read', 'SupportTicket:write',])]
    public string $authorName;

    #[ORM\Column(type: 'datetime_immutable')]
    #[ApiFilter(OrderFilter::class)]
    #[Groups(['SupportTicket:read',])]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(groups: ['SupportTicket:read', 'SupportTicket:write',])]
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[Assert\Callback(callback: [SupportTicket::class, 'validateOrderId'])]
    public ?int $orderId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(groups: ['SupportTicket:read', 'SupportTicket:write',])]
    public ?array $orderData = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(groups: ['SupportTicket:read'])]
    public ?string $processInstanceKey = null;

    #[ApiProperty(readableLink: false, types: ['https://schema.org/author'])]
    #[Groups(['SupportTicket:read', 'SupportTicket:write'])]
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    public User $user;

    #[ORM\OneToMany(targetEntity: SupportTicketComment::class, mappedBy: 'supportTicket', cascade: [
        'persist',
        'remove',
    ])]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    public Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    #[Groups(['SupportTicket:read',])]
    public function getCurrentStatus(): string
    {
        if ($this->comments->isEmpty()) {
            return SupportTicketStatus::NEW->getDisplayName();
        }

        return $this->comments->first()->status->getDisplayName();
    }

    #[Groups(['SupportTicket:read',])]
    public function getCurrentStatusValue(): string
    {
        if ($this->comments->isEmpty()) {
            return SupportTicketStatus::NEW->value;
        }

        return $this->comments->first()->status->value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public static function validateOrderId($value, $context): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value)) {
            if (!preg_match('/^\d+$/', $value)) {
                $context->buildViolation('This value should be a valid integer.')
                    ->addViolation();
                return;
            }
            // Convert string to int for further processing
            // Note: This won't modify the original value, but validation will pass
        }

        if (!is_int($value) && !is_numeric($value)) {
            $context->buildViolation('This value should be of type int.')
                ->addViolation();
        }
    }

    #[Groups(['SupportTicket:read'])]
    public function getUserName(): string
    {
        return $this->user->getName();
    }
}
