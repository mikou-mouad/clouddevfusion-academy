<?php

namespace App\EventListener;

use App\Entity\Contact;
use App\Service\ContactNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Contact::class)]
class ContactNotifyListener
{
    public function __construct(
        private ContactNotificationService $notificationService
    ) {
    }

    public function postPersist(Contact $contact, LifecycleEventArgs $event): void
    {
        $this->notificationService->notifyNewContact($contact);
    }
}

