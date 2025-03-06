<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

#[HasUserMessage]
final class PropertySourceNotMappedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $propertyName)
    {
        parent::__construct(sprintf('All attempts to map the property "%s" were exhausted and no default value was provided.', $propertyName), 400);
    }

}
