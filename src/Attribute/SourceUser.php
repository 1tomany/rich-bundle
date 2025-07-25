<?php

namespace OneToMany\RichBundle\Attribute;

use Symfony\Component\Security\Core\User\UserInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceUser extends PropertySource
{
    /**
     * @param class-string<UserInterface> $class
     * @param string|list<non-empty-string>|callable|null $callback
     */
    public function __construct(
        public string $class,
        public string $getter = 'getId',
        bool $trim = true,
        bool $nullify = false,
        mixed $callback = null,
    ) {
        parent::__construct(null, $trim, $nullify, $callback);
    }
}
