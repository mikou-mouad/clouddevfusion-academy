<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Course;
use App\Entity\Lab;
use App\Entity\SyllabusModule;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Input Processor qui transforme les données avant la désérialisation
 * Gère la conversion du prix et la structure des données
 */
class CourseInputProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private SerializerInterface $serializer
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Course
    {
        // Si les données sont un tableau (JSON brut), les transformer
        if (is_array($data)) {
            // Convertir le prix en string si c'est un nombre
            if (isset($data['price']) && is_numeric($data['price'])) {
                $data['price'] = (string) $data['price'];
            }
            
            // S'assurer que le prix n'est jamais null
            if (!isset($data['price']) || $data['price'] === null) {
                $data['price'] = '0';
            }
            
            // Désérialiser en objet Course
            $course = $this->serializer->deserialize(
                json_encode($data),
                Course::class,
                'json',
                $context
            );
        } else {
            /** @var Course $course */
            $course = $data;
            
            // Convertir le prix en string si c'est un nombre
            if ($course->getPrice() !== null && is_numeric($course->getPrice())) {
                $course->setPrice((string) $course->getPrice());
            }
            
            // S'assurer que le prix n'est jamais null
            if ($course->getPrice() === null) {
                $course->setPrice('0');
            }
        }

        // Passer au processor suivant
        return $this->processor->process($course, $operation, $uriVariables, $context);
    }
}

