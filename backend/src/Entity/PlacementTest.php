<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'placement_tests')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['placement_test:read']]),
        new Post(denormalizationContext: ['groups' => ['placement_test:write']]),
        new Get(normalizationContext: ['groups' => ['placement_test:read']]),
        new Put(denormalizationContext: ['groups' => ['placement_test:write']]),
        new Delete(),
    ]
)]
class PlacementTest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['placement_test:read', 'course:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Course::class, inversedBy: 'placementTest')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Groups(['placement_test:read', 'placement_test:write'])]
    #[Assert\NotNull]
    private ?Course $course = null;

    #[ORM\Column(length: 255)]
    #[Groups(['placement_test:read', 'placement_test:write', 'course:read'])]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['placement_test:read', 'placement_test:write', 'course:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['placement_test:read', 'placement_test:write'])]
    private int $passingScore = 70;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['placement_test:read', 'placement_test:write'])]
    private int $timeLimit = 30; // en minutes

    #[ORM\OneToMany(targetEntity: PlacementQuestion::class, mappedBy: 'placementTest', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['placement_test:read', 'placement_test:write'])]
    private Collection $questions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['placement_test:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['placement_test:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['placement_test:read', 'placement_test:write'])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPassingScore(): int
    {
        return $this->passingScore;
    }

    public function setPassingScore(int $passingScore): self
    {
        $this->passingScore = $passingScore;
        return $this;
    }

    public function getTimeLimit(): int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(int $timeLimit): self
    {
        $this->timeLimit = $timeLimit;
        return $this;
    }

    /**
     * @return Collection<int, PlacementQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(PlacementQuestion $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setPlacementTest($this);
        }
        return $this;
    }

    public function removeQuestion(PlacementQuestion $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getPlacementTest() === $this) {
                $question->setPlacementTest(null);
            }
        }
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}
