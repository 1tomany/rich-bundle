<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceSecurity extends PropertySource
{
    public function __construct(
        public bool $trim = true,
        public bool $nullify = false,
        public ?string $userClass = null,
        public ?string $pkeyProperty = null,
    ) {
    }
}
