<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityTitle = null,
        ?User $user = null,
        ?array $changes = null,
        ?string $description = null
    ): void {
        $log = new AuditLog();
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setEntityTitle($entityTitle);
        $log->setChanges($changes);
        $log->setDescription($description);

        if ($user) {
            $log->setUserEmail($user->getEmail());
            $log->setUsername($user->getUsername());
        }

        // Récupérer l'IP depuis la requête
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $ipAddress = $request->getClientIp();
            $log->setIpAddress($ipAddress);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logCreate(string $entityType, ?int $entityId, ?string $entityTitle, ?User $user): void
    {
        $this->log('create', $entityType, $entityId, $entityTitle, $user, null, "Création de {$entityType}" . ($entityTitle ? " : {$entityTitle}" : ""));
    }

    public function logUpdate(string $entityType, ?int $entityId, ?string $entityTitle, ?User $user, array $changes): void
    {
        $this->log('update', $entityType, $entityId, $entityTitle, $user, $changes, "Modification de {$entityType}");
    }

    public function logDelete(string $entityType, ?int $entityId, ?string $entityTitle, ?User $user): void
    {
        $this->log('delete', $entityType, $entityId, $entityTitle, $user, null, "Suppression de {$entityType}");
    }

    public function logLogin(?User $user): void
    {
        if ($user) {
            $this->log('login', 'User', $user->getId(), $user->getUsername(), $user, null, "Connexion de l'utilisateur");
        }
    }

    public function logLogout(?User $user): void
    {
        if ($user) {
            $this->log('logout', 'User', $user->getId(), $user->getUsername(), $user, null, "Déconnexion de l'utilisateur");
        }
    }
}
