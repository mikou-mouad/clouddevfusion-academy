<?php

namespace App\Tests\Security;

use App\Security\ApiAuthenticationEntryPoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticationEntryPointTest extends TestCase
{
    public function testStartReturnsUnauthorizedJsonResponse(): void
    {
        // Arrange
        $entryPoint = new ApiAuthenticationEntryPoint();
        $request = new Request();

        // Act
        $response = $entryPoint->start($request);

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertSame('Authentification requise.', $data['message']);
        $this->assertSame('Unauthorized', $data['error']);

        $this->assertSame(
            'application/json',
            $response->headers->get('Content-Type')
        );
    }
}