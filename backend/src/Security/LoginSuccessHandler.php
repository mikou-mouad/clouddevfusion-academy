<?php

namespace App\Security;

use App\Service\AuditLogService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private ?AuditLogService $auditLogService = null
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        
        // Vérifier que l'utilisateur est actif
        if (!$user->isActive()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Votre compte est désactivé'
            ], Response::HTTP_FORBIDDEN);
        }
        
        // Enregistrer le log de connexion (si le service est disponible)
        if ($this->auditLogService) {
            try {
                $this->auditLogService->logLogin($user);
            } catch (\Exception $e) {
                // Ne pas bloquer la connexion si l'audit échoue
                error_log('Erreur lors de l\'enregistrement du log de connexion: ' . $e->getMessage());
            }
        }
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
            ]
        ], Response::HTTP_OK);
    }
}
