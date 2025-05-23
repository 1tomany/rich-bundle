<?php

namespace OneToMany\RichBundle\Contract;

use function json_decode;
use function json_encode;
use function strval;

abstract readonly class AbstractJsonSchema implements JsonSchemaInterface
{
    public function __construct()
    {
    }

    public function __toString(): string
    {
        return strval(json_encode(static::schema()));
    }

    public function asObject(): object
    {
        /** @var object */
        return json_decode($this, false);
    }

    public function getName(): string
    {
        /** @var non-empty-string */
        return new \ReflectionClass($this)->getShortName();
    }
}
