<?php

namespace OneToMany\RichBundle\Contract;

use function json_decode;
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

    public function asObject(): object
    {
        // @phpstan-ignore return.type
        return json_decode($this, false);
    }

    public function getName(): string
    {
        // @phpstan-ignore return.type
        return new \ReflectionClass($this)->getShortName();
    }
}
