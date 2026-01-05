<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\State\ExamVoucherProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'exam_vouchers')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['exam_voucher:read']]),
        new Post(denormalizationContext: ['groups' => ['exam_voucher:write']]),
        new Get(normalizationContext: ['groups' => ['exam_voucher:read']]),
        new Put(denormalizationContext: ['groups' => ['exam_voucher:write']]),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['exam_voucher:read']],
    denormalizationContext: ['groups' => ['exam_voucher:write']]
)]
class ExamVoucher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['exam_voucher:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $code = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $examCode = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['voucher-only', 'training-voucher', 'retake'])]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?int $validityPeriod = null; // En jours

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?array $bookingSteps = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $rescheduleRules = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $redemptionInfo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $scheduleLocation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private ?string $idRequirements = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['exam_voucher:read', 'exam_voucher:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['exam_voucher:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['exam_voucher:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getExamCode(): ?string
    {
        return $this->examCode;
    }

    public function setExamCode(string $examCode): static
    {
        $this->examCode = $examCode;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getValidityPeriod(): ?int
    {
        return $this->validityPeriod;
    }

    public function setValidityPeriod(int $validityPeriod): static
    {
        $this->validityPeriod = $validityPeriod;
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

    public function getBookingSteps(): ?array
    {
        return $this->bookingSteps;
    }

    public function setBookingSteps(?array $bookingSteps): static
    {
        $this->bookingSteps = $bookingSteps;
        return $this;
    }

    public function getRescheduleRules(): ?string
    {
        return $this->rescheduleRules;
    }

    public function setRescheduleRules(?string $rescheduleRules): static
    {
        $this->rescheduleRules = $rescheduleRules;
        return $this;
    }

    public function getRedemptionInfo(): ?string
    {
        return $this->redemptionInfo;
    }

    public function setRedemptionInfo(?string $redemptionInfo): static
    {
        $this->redemptionInfo = $redemptionInfo;
        return $this;
    }

    public function getScheduleLocation(): ?string
    {
        return $this->scheduleLocation;
    }

    public function setScheduleLocation(?string $scheduleLocation): static
    {
        $this->scheduleLocation = $scheduleLocation;
        return $this;
    }

    public function getIdRequirements(): ?string
    {
        return $this->idRequirements;
    }

    public function setIdRequirements(?string $idRequirements): static
    {
        $this->idRequirements = $idRequirements;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
