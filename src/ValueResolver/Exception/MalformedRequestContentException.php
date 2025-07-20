<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\RuntimeException;

use function sprintf;

#[HasUserMessage]
final class MalformedRequestContentException extends RuntimeException
{
    public function __construct(string $format, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The request content is expected to be "%s" but could not be decoded because it is malformed.', $format), 400, $previous);
    }
}
