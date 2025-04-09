<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

use function sprintf;

#[HasUserMessage]
final class MalformedRequestContentException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $format, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The request content is expected to be "%s" but could not be decoded because it is malformed.', $format), 400, $previous);
    }
}
