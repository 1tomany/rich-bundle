<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;

use function sprintf;

#[HasUserMessage]
final class SourceSecurityMappingFailedTokenStorageIsNullException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $property)
    {
        parent::__construct(sprintf('The property "%s" could not be extracted from the security token because the Symfony Security Bundle is not installed. Try running "composer require symfony/security-bundle".', $property), 400);
    }
}
