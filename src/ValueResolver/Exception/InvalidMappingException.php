<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;

#[HasUserMessage]
final class InvalidMappingException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The request could not be processed because the content is malformed and could not be mapped correctly.', 400, $previous);
    }
}
