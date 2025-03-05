<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceSecurity extends PropertySource
{

    public function __construct(bool $required = true)
    {
        parent::__construct(null, $required);
    }

    public function getSource(): string
    {
        return 'security token';
    }

}
