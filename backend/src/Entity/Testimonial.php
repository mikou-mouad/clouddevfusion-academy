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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: 'testimonials')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['testimonial:read']]),
        new Post(denormalizationContext: ['groups' => ['testimonial:write']]),
        new Get(normalizationContext: ['groups' => ['testimonial:read']]),
        new Put(denormalizationContext: ['groups' => ['testimonial:write']]),
        new Delete(),
    ]
)]
class Testimonial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['testimonial:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(min: 10, max: 1000)]
    #[Groups(['testimonial:read', 'testimonial:write'])]
    private ?string $quote = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['testimonial:read', 'testimonial:write'])]
    private ?string $author = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['testimonial:read', 'testimonial:write'])]
    private ?string $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['testimonial:read', 'testimonial:write'])]
    private ?string $company = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Type(type: 'integer')]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['testimonial:read', 'testimonial:write'])]
    private ?int $rating = 5;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['testimonial:read', 'testimonial:write'])]
    private ?string $videoUrl = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuote(): ?string
    {
        return $this->quote;
    }

    public function setQuote(?string $quote): static
    {
        $this->quote = $quote;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating ?? 5;
        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;
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

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // Au moins une vidéo OU tous les champs texte doivent être remplis
        $hasVideo = !empty($this->videoUrl);
        $hasTextFields = !empty($this->quote) && !empty($this->author) && !empty($this->role) && !empty($this->company);

        if (!$hasVideo && !$hasTextFields) {
            $context->buildViolation('Vous devez fournir soit une vidéo, soit tous les champs texte (citation, auteur, rôle, entreprise).')
                ->atPath('videoUrl')
                ->addViolation();
        }

        // Si quote est fourni, il doit avoir au moins 10 caractères
        if (!empty($this->quote) && strlen($this->quote) < 10) {
            $context->buildViolation('La citation doit contenir au moins 10 caractères.')
                ->atPath('quote')
                ->addViolation();
        }
    }
}

