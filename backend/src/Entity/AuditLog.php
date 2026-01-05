<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['audit_log:read']]),
        new Get(normalizationContext: ['groups' => ['audit_log:read']]),
    ],
    normalizationContext: ['groups' => ['audit_log:read']]
)]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['audit_log:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['audit_log:read'])]
    private ?string $action = null; // 'create', 'update', 'delete', 'login', 'logout'

    #[ORM\Column(length: 100)]
    #[Groups(['audit_log:read'])]
    private ?string $entityType = null; // 'Course', 'BlogPost', 'Testimonial', etc.

    #[ORM\Column(nullable: true)]
    #[Groups(['audit_log:read'])]
    private ?int $entityId = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['audit_log:read'])]
    private ?string $entityTitle = null;

    #[ORM\Column(length: 255)]
    #[Groups(['audit_log:read'])]
    private ?string $userEmail = null;

    #[ORM\Column(length: 100)]
    #[Groups(['audit_log:read'])]
    private ?string $username = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['audit_log:read'])]
    private ?array $changes = null; // Anciennes et nouvelles valeurs

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['audit_log:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['audit_log:read'])]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['audit_log:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getEntityTitle(): ?string
    {
        return $this->entityTitle;
    }

    public function setEntityTitle(?string $entityTitle): static
    {
        $this->entityTitle = $entityTitle;
        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): static
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function setChanges(?array $changes): static
    {
        $this->changes = $changes;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
