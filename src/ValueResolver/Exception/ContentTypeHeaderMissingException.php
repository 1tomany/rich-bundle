<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\RuntimeException;

#[HasUserMessage]
final class ContentTypeHeaderMissingException extends RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The request content could not be parsed because the Content-Type header was missing or malformed.', 422, $previous);
    }
}
