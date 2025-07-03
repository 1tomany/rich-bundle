<?php

namespace OneToMany\RichBundle\Contract;

use OneToMany\RichBundle\Contract\Exception\RuntimeException;

use function is_object;
use function json_decode;
use function json_encode;
use function json_last_error_msg;
use function rtrim;
use function sprintf;

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
        $json = json_decode($this, false);

        if (!is_object($json)) {
            throw new RuntimeException(sprintf('Encoding failed: %s.', rtrim(json_last_error_msg(), '.')));
        }

        return $json;
    }

    public function getName(): string
    {
        return new \ReflectionClass($this)->getShortName() ?: throw new RuntimeException('JSON schema name cannot be empty.');
    }
}
