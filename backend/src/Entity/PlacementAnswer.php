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

#[ORM\Entity]
#[ORM\Table(name: 'placement_answers')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['placement_answer:read']]),
        new Post(denormalizationContext: ['groups' => ['placement_answer:write']]),
        new Get(normalizationContext: ['groups' => ['placement_answer:read']]),
        new Put(denormalizationContext: ['groups' => ['placement_answer:write']]),
        new Delete(),
    ]
)]
class PlacementAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['placement_answer:read', 'placement_question:read', 'placement_test:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlacementQuestion::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['placement_answer:read', 'placement_answer:write'])]
    #[Assert\NotNull]
    private ?PlacementQuestion $question = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['placement_answer:read', 'placement_answer:write', 'placement_question:read', 'placement_test:read'])]
    #[Assert\NotBlank]
    private ?string $text = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['placement_answer:read', 'placement_answer:write', 'placement_question:read', 'placement_test:read'])]
    private string $score = '0.00';

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['placement_answer:read', 'placement_answer:write', 'placement_question:read', 'placement_test:read'])]
    private bool $isCorrect = false;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['placement_answer:read', 'placement_answer:write', 'placement_question:read', 'placement_test:read'])]
    private int $orderIndex = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['placement_answer:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['placement_answer:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?PlacementQuestion
    {
        return $this->question;
    }

    public function setQuestion(?PlacementQuestion $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getScore(): string
    {
        return $this->score;
    }

    public function setScore(string $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;
        return $this;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
