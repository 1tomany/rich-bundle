<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\LogicException;

use function sprintf;

#[HasUserMessage]
final class ResolutionFailedSecurityBundleMissingException extends LogicException
{
    public function __construct(string $property, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The property "%s" could not be extracted from the security token because the Symfony Security Bundle is not installed. Try running "composer require symfony/security-bundle".', $property), previous: $previous);
    }
}
