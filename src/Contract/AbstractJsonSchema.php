<?php

namespace OneToMany\RichBundle\Contract;

use function is_object;
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
        return is_object($object = json_decode($this, false)) ? $object : throw new \RuntimeException('not an object');

        /*
        $object = json_decode($this, false);

        if (!\is_object($object)) {
            throw new \RuntimeException('failed to convert to json object');
        }

        return $object;
        */
    }

    public function getName(): string
    {
        $name = new \ReflectionClass($this)->getShortName();

        if (empty($name)) {
            throw new \RuntimeException('non empty name');
        }

        return $name;
    }
}
