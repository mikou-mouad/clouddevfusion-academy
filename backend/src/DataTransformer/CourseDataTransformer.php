<?php

namespace App\DataTransformer;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Course;
use App\Entity\Lab;
use App\Entity\SyllabusModule;
use Doctrine\ORM\EntityManagerInterface;

class CourseDataTransformer implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProcessorInterface $processor
    ) {
    }

    public function process(mixed $data, Post|Put $operation, array $uriVariables = [], array $context = []): Course
    {
        /** @var Course $course */
        $course = $data;

        // Traiter les modules du syllabus
        if ($course->getSyllabus()->count() > 0) {
            foreach ($course->getSyllabus() as $index => $module) {
                $module->setOrderIndex($index);
                $module->setCourse($course);

                // Traiter les labs du module
                if ($module->getLabs()->count() > 0) {
                    foreach ($module->getLabs() as $lab) {
                        $lab->setModule($module);
                    }
                }
            }
        }

        return $this->processor->process($course, $operation, $uriVariables, $context);
    }
}

