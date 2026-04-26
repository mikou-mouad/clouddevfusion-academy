<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Pour les requêtes API : retourne 401 JSON au lieu de rediriger vers /api/login.
 * Évite que le client suive une 302 et envoie GET /api/login (405).
 */
class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'success' => false,
            'message' => 'Authentification requise.',
            'error' => 'Unauthorized'
        ], Response::HTTP_UNAUTHORIZED, [
            'Content-Type' => 'application/json',
        ]);
    }
}
