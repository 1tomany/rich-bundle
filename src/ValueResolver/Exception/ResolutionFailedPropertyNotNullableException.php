<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\HttpException;

use function sprintf;

#[HasUserMessage]
final class ResolutionFailedPropertyNotNullableException extends HttpException
{
    public function __construct(string $property, ?\Throwable $previous = null)
    {
        parent::__construct(400, sprintf('Resolving the request failed because the property "%s" is not nullable.', $property), $previous);
    }
}
