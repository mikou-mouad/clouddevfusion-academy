<?php

namespace App\EventListener;

use App\Entity\PlacementTestResult;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: PlacementTestResult::class)]
class PlacementTestResultPersistListener
{
    public function prePersist(PlacementTestResult $result): void
    {
        $score = (float) $result->getScore();
        $score = max(0.0, min(100.0, $score));
        $result->setScore(number_format($score, 2, '.', ''));
    }
}
