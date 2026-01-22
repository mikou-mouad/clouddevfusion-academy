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
#[ORM\Table(name: 'placement_questions')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['placement_question:read']]),
        new Post(denormalizationContext: ['groups' => ['placement_question:write']]),
        new Get(normalizationContext: ['groups' => ['placement_question:read']]),
        new Put(denormalizationContext: ['groups' => ['placement_question:write']]),
        new Delete(),
    ]
)]
class PlacementQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['placement_question:read', 'placement_test:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlacementTest::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['placement_question:read', 'placement_question:write'])]
    #[Assert\NotNull]
    private ?PlacementTest $placementTest = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['placement_question:read', 'placement_question:write', 'placement_test:read'])]
    #[Assert\NotBlank]
    private ?string $question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['placement_question:read', 'placement_question:write', 'placement_test:read'])]
    private ?string $explanation = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['placement_question:read', 'placement_question:write'])]
    private int $orderIndex = 0;

    #[ORM\OneToMany(targetEntity: PlacementAnswer::class, mappedBy: 'question', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['placement_question:read', 'placement_question:write', 'placement_test:read'])]
    private Collection $answers;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['placement_question:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['placement_question:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
        $this->createdAt = new \DateTime();
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

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(?string $explanation): self
    {
        $this->explanation = $explanation;
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

    /**
     * @return Collection<int, PlacementAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(PlacementAnswer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setQuestion($this);
        }
        return $this;
    }

    public function removeAnswer(PlacementAnswer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
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
}
