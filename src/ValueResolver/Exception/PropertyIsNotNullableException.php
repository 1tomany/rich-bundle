<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\RuntimeException;

use function sprintf;

#[HasUserMessage]
final class PropertyIsNotNullableException extends RuntimeException
{
    public function __construct(string $property, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The value of the property "%s" is empty but is not nullable.', $property), 400, $previous);
    }
}
