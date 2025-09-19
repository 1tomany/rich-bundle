<?php

namespace OneToMany\RichBundle\Tests\Security;

use OneToMany\RichBundle\Security\FailureHandlerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class FailureHandlerTraitTest extends TestCase
{
    use FailureHandlerTrait;

    public function testOnAuthenticationFailureThrowsHttpException(): void
    {
        $authenticationExceptionMessage = 'Invalid credentials.';

        $this->expectException(HttpExceptionInterface::class);
        $this->expectExceptionCode(Response::HTTP_UNAUTHORIZED);
        $this->expectExceptionMessage($authenticationExceptionMessage);

        $this->onAuthenticationFailure(new Request(), new AuthenticationException($authenticationExceptionMessage));
    }
}
