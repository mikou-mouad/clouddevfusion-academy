<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Course;
use App\Entity\Lab;
use App\Entity\SyllabusModule;
use Doctrine\ORM\EntityManagerInterface;

/**
 * State Processor qui transforme les données avant de les traiter
 * Gère la conversion du prix et la désérialisation des collections
 */
class CourseStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProcessorInterface $persistProcessor
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Course
    {
        // Si les données sont encore un tableau (données brutes JSON)
        if (is_array($data)) {
            $course = new Course();
            $this->populateCourseFromArray($course, $data);
        } elseif ($data instanceof Course) {
            $course = $data;
            // Convertir le prix en string si c'est un nombre
            if (is_numeric($course->getPrice())) {
                $course->setPrice((string) $course->getPrice());
            }
        } else {
            throw new \InvalidArgumentException('Invalid data type for Course');
        }

        // Traiter les modules du syllabus
        $this->processSyllabus($course);

        // Utiliser le processor de persistance standard
        return $this->persistProcessor->process($course, $operation, $uriVariables, $context);
    }

    private function populateCourseFromArray(Course $course, array $data): void
    {
        $course->setTitle($data['title'] ?? '');
        $course->setCode($data['code'] ?? '');
        $course->setLevel($data['level'] ?? '');
        $course->setDuration($data['duration'] ?? '');
        $course->setFormat($data['format'] ?? '');
        $course->setPrice(is_numeric($data['price'] ?? 0) ? (string) $data['price'] : ($data['price'] ?? '0'));
        $course->setRole($data['role'] ?? '');
        $course->setProduct($data['product'] ?? null);
        $course->setLanguage($data['language'] ?? 'fr');
        $course->setDescription($data['description'] ?? null);
        $course->setCertification($data['certification'] ?? null);
        $course->setPopular($data['popular'] ?? false);
        $course->setObjectives($data['objectives'] ?? []);
        $course->setOutcomes($data['outcomes'] ?? []);
        $course->setPrerequisites($data['prerequisites'] ?? []);
        $course->setTargetRoles($data['targetRoles'] ?? []);

        if (isset($data['nextDate']) && !empty($data['nextDate'])) {
            try {
                $course->setNextDate(new \DateTime($data['nextDate']));
            } catch (\Exception $e) {
                // Ignorer si la date est invalide
            }
        }

        // Traiter le syllabus
        if (isset($data['syllabus']) && is_array($data['syllabus'])) {
            foreach ($data['syllabus'] as $index => $moduleData) {
                if (is_array($moduleData)) {
                    $module = new SyllabusModule();
                    $module->setTitle($moduleData['title'] ?? '');
                    $module->setDescription($moduleData['description'] ?? null);
                    $module->setOrderIndex($index);
                    $module->setCourse($course);

                    // Traiter les labs
                    if (isset($moduleData['labs']) && is_array($moduleData['labs'])) {
                        foreach ($moduleData['labs'] as $labData) {
                            if (is_array($labData)) {
                                $lab = new Lab();
                                $lab->setName($labData['name'] ?? '');
                                $lab->setDuration($labData['duration'] ?? null);
                                $lab->setModule($module);
                                $module->addLab($lab);
                            }
                        }
                    }

                    $course->addSyllabus($module);
                }
            }
        }
    }

    private function processSyllabus(Course $course): void
    {
        $syllabusCollection = $course->getSyllabus();
        $syllabusArray = $syllabusCollection->toArray();
        $course->getSyllabus()->clear();

        foreach ($syllabusArray as $index => $moduleData) {
            $module = null;

            if (is_array($moduleData)) {
                $module = new SyllabusModule();
                $module->setTitle($moduleData['title'] ?? '');
                $module->setDescription($moduleData['description'] ?? null);

                if (isset($moduleData['labs']) && is_array($moduleData['labs'])) {
                    foreach ($moduleData['labs'] as $labData) {
                        $lab = new Lab();
                        if (is_array($labData)) {
                            $lab->setName($labData['name'] ?? '');
                            $lab->setDuration($labData['duration'] ?? null);
                        }
                        $lab->setModule($module);
                        $module->addLab($lab);
                    }
                }
            } elseif ($moduleData instanceof SyllabusModule) {
                $module = new SyllabusModule();
                $module->setTitle($moduleData->getTitle());
                $module->setDescription($moduleData->getDescription());

                foreach ($moduleData->getLabs() as $labData) {
                    $lab = new Lab();
                    if ($labData instanceof Lab) {
                        $lab->setName($labData->getName());
                        $lab->setDuration($labData->getDuration());
                    } elseif (is_array($labData)) {
                        $lab->setName($labData['name'] ?? '');
                        $lab->setDuration($labData['duration'] ?? null);
                    }
                    $lab->setModule($module);
                    $module->addLab($lab);
                }
            }

            if ($module) {
                $module->setOrderIndex($index);
                $module->setCourse($course);
                $course->addSyllabus($module);
            }
        }
    }
}

