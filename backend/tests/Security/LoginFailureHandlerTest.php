<?php

namespace App\Tests\Security;

use App\Security\LoginFailureHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LoginFailureHandlerTest extends TestCase
{
    public function testAuthenticationFailureReturnsUnauthorizedJsonResponse(): void
    {
        // Arrange : préparer les données du test
        $handler = new LoginFailureHandler();
        $request = new Request();
        $exception = new AuthenticationException('Mauvais mot de passe');

        // Act : exécuter la méthode que l'on veut tester
        $response = $handler->onAuthenticationFailure($request, $exception);

        // Assert : vérifier que le résultat est celui attendu
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertSame('Identifiants incorrects', $data['message']);
        $this->assertSame('Mauvais mot de passe', $data['error']);
    }
}