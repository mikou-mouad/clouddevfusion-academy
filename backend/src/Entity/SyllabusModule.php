<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'syllabus_modules')]
class SyllabusModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['course:read'])]
    private ?int $orderIndex = 0;

    #[ORM\ManyToOne(inversedBy: 'syllabus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: Lab::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['course:read', 'course:write'])]
    #[MaxDepth(1)]
    private Collection $labs;

    public function __construct()
    {
        $this->labs = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): static
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    /**
     * @return Collection<int, Lab>
     */
    public function getLabs(): Collection
    {
        return $this->labs;
    }

    public function addLab(Lab $lab): static
    {
        if (!$this->labs->contains($lab)) {
            $this->labs->add($lab);
            $lab->setModule($this);
        }

        return $this;
    }

    public function removeLab(Lab $lab): static
    {
        if ($this->labs->removeElement($lab)) {
            if ($lab->getModule() === $this) {
                $lab->setModule(null);
            }
        }

        return $this;
    }
}

