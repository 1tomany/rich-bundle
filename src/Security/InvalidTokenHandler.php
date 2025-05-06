<?php

namespace OneToMany\RichBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class InvalidTokenHandler
{
    public static function create(): AuthenticationFailureHandlerInterface
    {
        if (!\interface_exists(AuthenticationFailureHandlerInterface::class)) {
            throw new \LogicException('no auth interface');
        }

        return new class() implements AuthenticationFailureHandlerInterface {
            public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
            {
                throw HttpException::fromStatusCode(401, $exception->getMessage(), $exception);
            }
        };
    }
}
