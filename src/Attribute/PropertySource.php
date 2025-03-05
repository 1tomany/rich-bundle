<?php

namespace OneToMany\RichBundle\Attribute;

abstract readonly class PropertySource
{

    public function __construct(
        public ?string $name = null,
        public bool $required = true,
    )
    {
    }

    abstract public function getSource(): string;

}
