<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Fixture;

use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;

/**
 * @implements InputInterface<EmptyCommand>
 */
final class EmptyInput implements InputInterface
{
    public function __construct()
    {
    }

    public function toCommand(): CommandInterface
    {
        return new EmptyCommand();
    }
}
