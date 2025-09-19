<?php

namespace OneToMany\RichBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

trait FailureHandlerTrait // @phpstan-ignore trait.unused
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        throw HttpException::fromStatusCode(401, $exception->getMessage(), $exception);
    }
}
