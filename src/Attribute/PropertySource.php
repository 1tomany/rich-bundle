<?php

namespace OneToMany\RichBundle\Attribute;

use OneToMany\RichBundle\Exception\RuntimeException;

use function is_callable;

abstract readonly class PropertySource
{
    /**
     * @param string|list<non-empty-string>|callable|null $callback
     */
    public function __construct(
        public ?string $name = null,
        public bool $trim = true,
        public bool $nullify = false,
        public mixed $callback = null,
    ) {
        if (null !== $callback && !is_callable($callback)) {
            throw new RuntimeException('The callback parameter is not callable: ensure it references a closure, function, or public static class method.');
        }
    }

    public function getName(string $property): string
    {
        return $this->name ?? $property;
    }
}
