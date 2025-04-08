<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

#[HasUserMessage]
final class PropertyIsNotNullableException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $property)
    {
        parent::__construct(\sprintf('The property "%s" must be a non-empty string with at least one non-whitespace character.', $property), 400);
    }
}
