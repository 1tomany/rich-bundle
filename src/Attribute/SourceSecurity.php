<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceSecurity extends PropertySource
{
    public function __construct(
        bool $trim = true,
        bool $nullify = false,
    ) {
        parent::__construct(null, $trim, $nullify);
    }
}
