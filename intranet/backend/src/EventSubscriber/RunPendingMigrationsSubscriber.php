<?php

namespace App\EventSubscriber;

use App\Service\PendingMigrationRunner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class RunPendingMigrationsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PendingMigrationRunner $migrationRunner,
        private readonly KernelInterface $kernel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 512]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->migrationRunner->runIfPending($this->kernel->getProjectDir());
    }
}
