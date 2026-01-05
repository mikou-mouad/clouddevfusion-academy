<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'home_banners')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['home_banner:read']]),
        new Post(denormalizationContext: ['groups' => ['home_banner:write']]),
        new Get(normalizationContext: ['groups' => ['home_banner:read']]),
        new Put(denormalizationContext: ['groups' => ['home_banner:write']]),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['home_banner:read']],
    denormalizationContext: ['groups' => ['home_banner:write']]
)]
class HomeBanner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['home_banner:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private ?string $logoPath = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private ?string $kpi1Number = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private ?string $kpi1Label = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private ?string $kpi2Number = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private ?string $kpi2Label = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private ?string $kpi3Number = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private ?string $kpi3Label = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['home_banner:read', 'home_banner:write'])]
    private bool $active = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;
        return $this;
    }

    public function getKpi1Number(): ?string
    {
        return $this->kpi1Number;
    }

    public function setKpi1Number(?string $kpi1Number): static
    {
        $this->kpi1Number = $kpi1Number;
        return $this;
    }

    public function getKpi1Label(): ?string
    {
        return $this->kpi1Label;
    }

    public function setKpi1Label(?string $kpi1Label): static
    {
        $this->kpi1Label = $kpi1Label;
        return $this;
    }

    public function getKpi2Number(): ?string
    {
        return $this->kpi2Number;
    }

    public function setKpi2Number(?string $kpi2Number): static
    {
        $this->kpi2Number = $kpi2Number;
        return $this;
    }

    public function getKpi2Label(): ?string
    {
        return $this->kpi2Label;
    }

    public function setKpi2Label(?string $kpi2Label): static
    {
        $this->kpi2Label = $kpi2Label;
        return $this;
    }

    public function getKpi3Number(): ?string
    {
        return $this->kpi3Number;
    }

    public function setKpi3Number(?string $kpi3Number): static
    {
        $this->kpi3Number = $kpi3Number;
        return $this;
    }

    public function getKpi3Label(): ?string
    {
        return $this->kpi3Label;
    }

    public function setKpi3Label(?string $kpi3Label): static
    {
        $this->kpi3Label = $kpi3Label;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
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
}
