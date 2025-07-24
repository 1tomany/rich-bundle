<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\LogicException;

use function sprintf;

final class ResolutionFailedUserGetterMissingException extends LogicException
{
    public function __construct(string $property, string $class, string $getter, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Resolving the property "%s" failed because the getter method "%s::%s()" does not exist.', $property, $class, $getter), previous: $previous);
    }
}
