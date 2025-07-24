<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\HttpException;

#[HasUserMessage]
final class ResolutionFailedMappingRequestFailedException extends HttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(400, 'Resolving the request failed because it is is malformed and could not be mapped correctly.', $previous);
    }
}
