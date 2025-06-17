<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceContent extends PropertySource
{
    public function __construct(
        public bool $trim = true,
        public bool $nullify = false,
    ) {
    }
}
