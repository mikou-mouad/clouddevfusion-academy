<?php

namespace App\EventListener;

use App\Entity\PlacementTestResult;
use App\Service\PlacementTestResultNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: PlacementTestResult::class)]
class PlacementTestResultNotifyListener
{
    public function __construct(
        private PlacementTestResultNotificationService $notificationService
    ) {
    }

    public function postPersist(PlacementTestResult $result, LifecycleEventArgs $event): void
    {
        $this->notificationService->notifyNewResult($result);
    }
}
