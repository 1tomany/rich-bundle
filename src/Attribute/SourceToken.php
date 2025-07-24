<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class SourceToken extends PropertySource
{
    public function __construct(
        bool $trim = true,
        bool $nullify = false,
    ) {
        parent::__construct(null, $trim, $nullify);
    }
}
