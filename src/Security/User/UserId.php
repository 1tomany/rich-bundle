<?php

namespace OneToMany\RichBundle\Security\User;

final readonly class UserId
{
    public function __construct(
        public int|string|null $id,
        public string $identifier,
    )
    {
    }
}
