<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\State\CourseProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'courses')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['course:read']]),
        new Post(processor: CourseProcessor::class, denormalizationContext: ['groups' => ['course:write']]),
        new Get(normalizationContext: ['groups' => ['course:read']]),
        new Put(processor: CourseProcessor::class, denormalizationContext: ['groups' => ['course:write']]),
        new Delete(),
    ]
)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['course:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $code = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $level = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $duration = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $format = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $accessDelay = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    #[Groups(['course:read', 'course:write'])]
    private ?string $price = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $role = null;

    #[ORM\Column(length: 100)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $product = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $language = 'fr';

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?\DateTimeInterface $nextDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $certification = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['course:read', 'course:write'])]
    private bool $popular = false;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['course:read', 'course:write'])]
    private array $objectives = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['course:read', 'course:write'])]
    private array $outcomes = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['course:read', 'course:write'])]
    private array $prerequisites = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['course:read', 'course:write'])]
    private array $targetRoles = [];

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: SyllabusModule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    #[Groups(['course:read', 'course:write'])]
    #[MaxDepth(2)]
    private Collection $syllabus;

    #[ORM\OneToOne(mappedBy: 'course', targetEntity: PlacementTest::class, cascade: ['persist', 'remove'])]
    #[Groups(['course:read'])]
    private ?PlacementTest $placementTest = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->syllabus = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
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

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function getAccessDelay(): ?string
    {
        return $this->accessDelay;
    }

    public function setAccessDelay(?string $accessDelay): static
    {
        $this->accessDelay = $accessDelay;
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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getProduct(): ?string
    {
        return $this->product;
    }

    public function setProduct(?string $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function getNextDate(): ?\DateTimeInterface
    {
        return $this->nextDate;
    }

    public function setNextDate(?\DateTimeInterface $nextDate): static
    {
        $this->nextDate = $nextDate;
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

    public function getCertification(): ?string
    {
        return $this->certification;
    }

    public function setCertification(?string $certification): static
    {
        $this->certification = $certification;
        return $this;
    }

    public function isPopular(): bool
    {
        return $this->popular;
    }

    public function setPopular(bool $popular): static
    {
        $this->popular = $popular;
        return $this;
    }

    public function getObjectives(): array
    {
        return $this->objectives;
    }

    public function setObjectives(array $objectives): static
    {
        $this->objectives = $objectives;
        return $this;
    }

    public function getOutcomes(): array
    {
        return $this->outcomes;
    }

    public function setOutcomes(array $outcomes): static
    {
        $this->outcomes = $outcomes;
        return $this;
    }

    public function getPrerequisites(): array
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(array $prerequisites): static
    {
        $this->prerequisites = $prerequisites;
        return $this;
    }

    public function getTargetRoles(): array
    {
        return $this->targetRoles;
    }

    public function setTargetRoles(array $targetRoles): static
    {
        $this->targetRoles = $targetRoles;
        return $this;
    }

    /**
     * @return Collection<int, SyllabusModule>
     */
    public function getSyllabus(): Collection
    {
        return $this->syllabus;
    }

    public function addSyllabus(SyllabusModule $syllabus): static
    {
        if (!$this->syllabus->contains($syllabus)) {
            $this->syllabus->add($syllabus);
            $syllabus->setCourse($this);
        }

        return $this;
    }

    public function removeSyllabus(SyllabusModule $syllabus): static
    {
        if ($this->syllabus->removeElement($syllabus)) {
            if ($syllabus->getCourse() === $this) {
                $syllabus->setCourse(null);
            }
        }

        return $this;
    }

    public function setSyllabus(array $syllabusModules): static
    {
        // Clear existing syllabus
        foreach ($this->syllabus as $module) {
            $this->removeSyllabus($module);
        }

        // Add new modules
        foreach ($syllabusModules as $index => $moduleData) {
            if (is_array($moduleData)) {
                $module = new SyllabusModule();
                $module->setTitle($moduleData['title'] ?? '');
                $module->setDescription($moduleData['description'] ?? null);
                $module->setOrderIndex($index);
                $module->setCourse($this);

                // Add labs
                if (isset($moduleData['labs']) && is_array($moduleData['labs'])) {
                    foreach ($moduleData['labs'] as $labData) {
                        $lab = new Lab();
                        $lab->setName($labData['name'] ?? '');
                        $lab->setDuration($labData['duration'] ?? null);
                        $lab->setModule($module);
                        $module->addLab($lab);
                    }
                }

                $this->addSyllabus($module);
            }
        }

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

    public function getPlacementTest(): ?PlacementTest
    {
        return $this->placementTest;
    }

    public function setPlacementTest(?PlacementTest $placementTest): static
    {
        $this->placementTest = $placementTest;
        return $this;
    }
}

