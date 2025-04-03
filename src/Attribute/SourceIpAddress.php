<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceIpAddress extends PropertySource
{
    public function __construct()
    {
        parent::__construct(null);
    }
}
