<?php

namespace OneToMany\RichBundle\Contract;

use function json_encode;

abstract readonly class AbstractJsonSchema implements JsonSchemaInterface
{
    public function __construct()
    {
    }

    public function __toString(): string
    {
        return (string) json_encode(static::schema());
    }
}
