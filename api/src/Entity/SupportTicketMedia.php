<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\SupportTicketMediaRepository;
use App\State\Processor\SupportTicketMediaCreateProcessor;
use App\State\Processor\SupportTicketMediaDeleteProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/support_tickets/{supportTicket}/media',
            uriVariables: [
                'supportTicket' => new Link(
                    fromProperty: 'supportTicket',
                    toProperty: 'supportTicket',
                    fromClass: SupportTicket::class,
                    toClass: SupportTicketMedia::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/support_tickets/{supportTicket}/media',
            uriVariables: [
                'supportTicket' => new Link(
                    fromProperty: 'supportTicket',
                    toProperty: 'supportTicket',
                    fromClass: SupportTicket::class,
                    toClass: SupportTicketMedia::class,
                ),
            ],
            processor: SupportTicketMediaCreateProcessor::class,
            deserialize: false,
            inputFormats: ['multipart' => ['multipart/form-data']],
        ),
        new Get(),
        new Delete(
            processor: SupportTicketMediaDeleteProcessor::class,
        ),
    ],
    normalizationContext: [
        AbstractNormalizer::GROUPS => ['SupportTicketMedia:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
    ],
    denormalizationContext: [
        AbstractNormalizer::GROUPS => ['SupportTicketMedia:write'],
    ],
    security: 'is_granted("OIDC_SUPPORT_EMPLOYEE") or is_granted("OIDC_SUPPORT_MANAGER")'
)]
#[ORM\Entity(repositoryClass: SupportTicketMediaRepository::class)]
class SupportTicketMedia
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[Groups(['SupportTicketMedia:read'])]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['SupportTicketMedia:read'])]
    public string $filename;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['SupportTicketMedia:read'])]
    public string $originalName;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['SupportTicketMedia:read'])]
    public string $mimeType;

    #[ORM\Column(type: 'bigint')]
    #[Groups(['SupportTicketMedia:read'])]
    public int $size;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 500)]
    #[Groups(['SupportTicketMedia:read'])]
    public string $path;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['SupportTicketMedia:read'])]
    public \DateTimeImmutable $createdAt;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: SupportTicket::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['SupportTicketMedia:read', 'SupportTicketMedia:write'])]
    public SupportTicket $supportTicket;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['SupportTicketMedia:read'])]
    public function getDownloadUrl(): string
    {
        return "/api/support_tickets/{$this->supportTicket->getId()}/media/{$this->id}/download";
    }
}
