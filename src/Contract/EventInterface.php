<?php

namespace OneToMany\RichBundle\Contract;

use Symfony\Component\Security\Core\User\UserInterface;

interface EventInterface
{
    public function getUser(): ?UserInterface;

    public function getType(): object;

    public function getData(): mixed;

    /**
     * @return list<string>
     */
    public function getSerializationGroups(): array;
}
