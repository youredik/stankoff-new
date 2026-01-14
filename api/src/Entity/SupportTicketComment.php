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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Enum\SupportTicketClosingReason;
use App\Enum\SupportTicketStatus;
use App\State\Processor\SupportTicketCommentCreateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            paginationClientItemsPerPage: true,
        ),
        new GetCollection(
            uriTemplate: '/support_tickets/{supportTicket}/comments',
            uriVariables: [
                'supportTicket' => new Link(
                    fromProperty: 'supportTicket',
                    toProperty: 'supportTicket',
                    fromClass: SupportTicket::class,
                    toClass: SupportTicketComment::class,
                ),
            ],
            order: ['createdAt' => 'DESC'],
        ),
        new Post(
            processor: SupportTicketCommentCreateProcessor::class,
        ),
        new Get(),
    ],
    normalizationContext: [
        AbstractNormalizer::GROUPS => ['SupportTicketComment:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
    ],
    denormalizationContext: [
        AbstractNormalizer::GROUPS => ['SupportTicketComment:write'],
    ],
    collectDenormalizationErrors: true,
    security: 'is_granted("OIDC_SUPPORT_EMPLOYEE") or is_granted("OIDC_SUPPORT_MANAGER")'
)]
#[ORM\Entity]
class SupportTicketComment
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[Groups(['SupportTicketComment:read', 'SupportTicketComment:write',])]
    #[ORM\Column(type: "text")]
//    #[Assert\NotBlank]
    public string $comment;

    #[Groups(['SupportTicketComment:read', 'SupportTicketComment:write',])]
    #[ORM\Column(type: 'string', nullable: true, enumType: SupportTicketClosingReason::class)]
    public ?SupportTicketClosingReason $closingReason = null;

    #[Groups(['SupportTicketComment:read', 'SupportTicketComment:write',])]
    #[ORM\Column(type: 'string', enumType: SupportTicketStatus::class)]
    public SupportTicketStatus $status;

    #[Groups(['SupportTicketComment:read',])]
    #[ApiFilter(OrderFilter::class)]
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ApiFilter(SearchFilter::class, strategy: SearchFilterInterface::STRATEGY_EXACT)]
    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: SupportTicket::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['SupportTicketComment:read', 'SupportTicketComment:write',])]
    public SupportTicket $supportTicket;

    #[ApiProperty(types: ['https://schema.org/author'])]
    #[Groups(['SupportTicketComment:read', 'SupportTicketComment:write',])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?UserInterface $user = null;

    #[Groups(['SupportTicketComment:read',])]
    public function getStatusDisplayName(): string
    {
        return $this->status->getDisplayName();
    }

    #[Groups(['SupportTicketComment:read',])]
    public function getClosingReasonDisplayName(): ?string
    {
        return $this->closingReason?->getDisplayName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
