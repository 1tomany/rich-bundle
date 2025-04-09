<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceIpAddress extends PropertySource
{
    public function __construct(
        public bool $trim = true,
        public bool $nullify = false,
    )
    {
        parent::__construct(null, $trim, $nullify);
    }
}
