<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\HttpException;

#[HasUserMessage]
final class ResolutionFailedContentTypeHeaderNotFoundException extends HttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(422, 'Resolving the request failed because the Content-Type header was missing or malformed.', $previous);
    }
}
