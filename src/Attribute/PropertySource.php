<?php

namespace OneToMany\RichBundle\Attribute;

use function trim;

abstract readonly class PropertySource
{
    public function __construct(
        public ?string $name = null,
        public bool $trim = true,
        public bool $nullify = false,
    ) {
    }

    public function getName(string $property): string
    {
        return trim($this->name ?? '') ?: $property;
    }
}
