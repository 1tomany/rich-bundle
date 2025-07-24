<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Exception\ExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[HasUserMessage]
final class ResolutionFailedContentTypeHeaderNotFoundException extends HttpException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(422, 'The request content could not be parsed because the Content-Type header was missing or malformed.', $previous);
    }
}
