<?php

namespace OneToMany\RichBundle\Contract;

interface JsonSchemaInterface
{
    public function __toString(): string;

    /**
     * @return array{
     *   title: non-empty-string,
     *   type: non-empty-string,
     *   properties: array<string, mixed>,
     *   required: list<non-empty-string>,
     *   additionalProperties: bool,
     * }
     */
    public static function schema(): array;
}
