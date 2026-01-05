<?php

namespace App\Serializer;

use App\Entity\Course;
use App\Entity\Lab;
use App\Entity\SyllabusModule;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CourseDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer
    ) {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Course
    {
        // Convertir le prix en string si c'est un nombre
        // S'assurer que le prix n'est jamais null (utiliser '0' par défaut)
        if (!isset($data['price']) || $data['price'] === null) {
            $data['price'] = '0';
        } elseif (is_numeric($data['price'])) {
            $data['price'] = (string) $data['price'];
        }

        // Sauvegarder le syllabus pour le traiter manuellement après désérialisation
        $syllabusData = $data['syllabus'] ?? null;
        
        // Retirer le syllabus des données pour éviter la désérialisation automatique
        unset($data['syllabus']);

        // Désérialiser le cours de base sans le syllabus
        $course = $this->normalizer->denormalize($data, $type, $format, $context);

        // Traiter le syllabus manuellement
        if ($syllabusData !== null && is_array($syllabusData)) {
            $syllabusCollection = $course->getSyllabus();
            $syllabusCollection->clear();

            foreach ($syllabusData as $index => $moduleData) {
                if (is_array($moduleData)) {
                    $moduleTitle = $moduleData['title'] ?? '';
                    if (empty($moduleTitle)) {
                        continue; // Ignorer les modules sans titre
                    }
                    
                    $module = new SyllabusModule();
                    $module->setTitle($moduleTitle);
                    $module->setDescription($moduleData['description'] ?? null);
                    $module->setOrderIndex($index);
                    $module->setCourse($course);

                    // Traiter les labs
                    if (isset($moduleData['labs']) && is_array($moduleData['labs'])) {
                        foreach ($moduleData['labs'] as $labData) {
                            if (is_array($labData)) {
                                $labName = $labData['name'] ?? '';
                                if (empty($labName)) {
                                    continue; // Ignorer les labs sans nom
                                }
                                
                                $lab = new Lab();
                                $lab->setName($labName);
                                $lab->setDuration($labData['duration'] ?? null);
                                $lab->setModule($module);
                                $module->addLab($lab);
                            }
                        }
                    }

                    $syllabusCollection->add($module);
                }
            }
        }

        return $course;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === Course::class && is_array($data);
    }
}

