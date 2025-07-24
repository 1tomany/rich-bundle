<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\LogicException;

use function sprintf;

final class ResolutionFailedSecurityBundleMissingException extends LogicException
{
    public function __construct(string $property, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Resolving the property "%s" failed because the Symfony Security Bundle is not installed. Try running "composer require symfony/security-bundle".', $property), previous: $previous);
    }
}
