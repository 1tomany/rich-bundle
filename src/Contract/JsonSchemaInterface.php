<?php

namespace OneToMany\RichBundle\Contract;

interface JsonSchemaInterface extends \Stringable
{
    public function __toString(): string;

    /**
     * @return array<string, mixed>
     */
    public static function schema(): array;

    /**
     * @return non-empty-string
     */
    public function getName(): string;
}
