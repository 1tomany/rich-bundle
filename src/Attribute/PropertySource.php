<?php

namespace OneToMany\RichBundle\Attribute;

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
        return $this->name ?? $property;
    }
}
