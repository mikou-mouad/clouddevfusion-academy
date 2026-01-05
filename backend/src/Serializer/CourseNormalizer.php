<?php

namespace App\Serializer;

use App\Entity\Course;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CourseNormalizer implements NormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer
    ) {
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // Ajouter un handler de référence circulaire qui retourne null
        $context['circular_reference_handler'] = function ($object) {
            return null;
        };
        
        $data = $this->normalizer->normalize($object, $format, $context);
        
        // Si c'est un Course, nettoyer les références circulaires dans le syllabus
        if ($object instanceof Course && isset($data['syllabus'])) {
            foreach ($data['syllabus'] as &$module) {
                // Retirer la référence au course dans chaque module
                unset($module['course']);
                
                // Nettoyer les labs pour éviter les références circulaires
                if (isset($module['labs'])) {
                    foreach ($module['labs'] as &$lab) {
                        // Retirer la référence au module dans chaque lab
                        unset($lab['module']);
                    }
                }
            }
        }
        
        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Course;
    }
}

