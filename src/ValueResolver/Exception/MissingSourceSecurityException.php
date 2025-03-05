<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

final class MissingSourceSecurityException extends \RuntimeException implements MissingSourceExceptionInterface
{

    public function __construct(string $propertyName)
    {
        parent::__construct(sprintf('The property "%s" could not be mapped from the security token because a security token could not be found.', $propertyName), 500);
    }

}
