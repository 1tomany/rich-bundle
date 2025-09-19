<?php

namespace OneToMany\RichBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

trait FailureHandlerTrait
{
    /**
     * This method throws the original AuthenticationException to
     * allow the user to invoke their own handler. Without it, the
     * default Symfony exception handler is invoked and formats the
     * response. By throwing the exception, we ensure that all API
     * responses are consistently formatted.
     *
     * @throws HttpExceptionInterface
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): never
    {
        throw HttpException::fromStatusCode(Response::HTTP_UNAUTHORIZED, $exception->getMessage(), $exception, code: Response::HTTP_UNAUTHORIZED);
    }
}
