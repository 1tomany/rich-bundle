<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\LogicException;

use function sprintf;

final class ResolutionFailedUserIncorrectTypeException extends LogicException
{
    public function __construct(string $property, string $class, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Resolving the property "%s" failed because the user object is not of type "%s".', $property, $class), previous: $previous);
    }
}
