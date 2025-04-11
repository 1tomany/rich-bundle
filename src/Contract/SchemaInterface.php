<?php

namespace OneToMany\RichBundle\Contract;

interface SchemaInterface
{
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
