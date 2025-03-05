<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\PropertySource;

final class MissingSourceException extends \RuntimeException implements MissingSourceExceptionInterface
{

    public function __construct(PropertySource $source, string $propertyName, string $sourceKeyName)
    {
        parent::__construct(sprintf('The property "%s" could not be mapped from the %s because a key named "%s" could not be found.', $propertyName, $source->getSource(), $sourceKeyName), 500);
    }

}
