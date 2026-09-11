<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\LoginSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginSuccessHandlerTest extends TestCase
{
    public function testDisabledUserCannotLogin(): void
    {
        // Arrange
        $user = new User();
        $user->setActive(false);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $handler = new LoginSuccessHandler();
        $request = new Request();

        // Act
        $response = $handler->onAuthenticationSuccess($request, $token);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertSame('Votre compte est désactivé', $data['message']);
    }
}