<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Course;
use App\Entity\Lab;
use App\Entity\SyllabusModule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CourseStateProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Cette méthode est appelée pour les opérations GET
        // On peut laisser API Platform gérer cela par défaut
        return null;
    }
}

