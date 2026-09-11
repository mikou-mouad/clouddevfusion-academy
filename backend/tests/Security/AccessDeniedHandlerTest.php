<?php

namespace App\Tests\Security;

use App\Security\AccessDeniedHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessDeniedHandlerTest extends TestCase
{
    public function testHandleReturnsForbiddenJsonResponse(): void
    {
        // Arrange
        $handler = new AccessDeniedHandler();
        $request = new Request();
        $exception = new AccessDeniedException();

        // Act
        $response = $handler->handle($request, $exception);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertSame(
            'Accès refusé. Vous n\'avez pas les permissions nécessaires.',
            $data['message']
        );
        $this->assertSame('Access denied', $data['error']);
    }
}