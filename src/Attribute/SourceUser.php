<?php

namespace OneToMany\RichBundle\Attribute;

use Symfony\Component\Security\Core\User\UserInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceUser extends PropertySource
{
    /**
     * @param class-string<UserInterface> $userClass
     */
    public function __construct(
        public string $userClass,
        public string $getter = 'getId',
        bool $trim = true,
        bool $nullify = false,
    ) {
        parent::__construct(null, $trim, $nullify);
    }
}
