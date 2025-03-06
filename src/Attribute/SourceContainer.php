<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceContainer extends PropertySource
{

    /*
    public function __construct(?string $name = null, bool $required = true)
    {
        parent::__construct([SourceType::Container], $name, $required);
    }

    public function getSource(): string
    {
        return 'container';
    }
    */

}
