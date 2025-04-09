<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

use function sprintf;

#[HasUserMessage]
final class PropertyIsNotNullableException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $property)
    {
        parent::__construct(sprintf('The property "%s" must have a non-empty value with at least one non-whitespace character.', $property), 400);
    }
}
