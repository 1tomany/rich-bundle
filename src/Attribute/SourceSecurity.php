<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceSecurity extends PropertySource
{
    public function __construct()
    {
        parent::__construct(null);
    }
}
