<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'placement_test_results')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['placement_result:read']]),
        new Post(denormalizationContext: ['groups' => ['placement_result:write']]),
        new Get(normalizationContext: ['groups' => ['placement_result:read']]),
    ]
)]
class PlacementTestResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['placement_result:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlacementTest::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private ?PlacementTest $placementTest = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private ?string $userEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private ?string $userName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private string $score = '0.00';

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private int $totalQuestions = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private int $correctAnswers = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private bool $passed = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['placement_result:read', 'placement_result:write'])]
    private ?array $answers = null; // {questionId: answerId}

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['placement_result:read'])]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['placement_result:read'])]
    private ?string $ipAddress = null;

    public function __construct()
    {
        $this->completedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlacementTest(): ?PlacementTest
    {
        return $this->placementTest;
    }

    public function setPlacementTest(?PlacementTest $placementTest): self
    {
        $this->placementTest = $placementTest;
        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(?string $userEmail): self
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): self
    {
        $this->userName = $userName;
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

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(int $totalQuestions): self
    {
        $this->totalQuestions = $totalQuestions;
        return $this;
    }

    public function getCorrectAnswers(): int
    {
        return $this->correctAnswers;
    }

    public function setCorrectAnswers(int $correctAnswers): self
    {
        $this->correctAnswers = $correctAnswers;
        return $this;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): self
    {
        $this->passed = $passed;
        return $this;
    }

    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(?array $answers): self
    {
        $this->answers = $answers;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
}
