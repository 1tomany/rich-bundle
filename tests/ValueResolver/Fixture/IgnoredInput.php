<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Fixture;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;

/**
 * @implements InputInterface<CommandInterface>
 */
final class IgnoredInput implements InputInterface
{

    #[PropertyIgnored]
    public string $name = 'Modesto';

    public function __construct()
    {
    }

    public function toCommand(): CommandInterface
    {
        return new class() implements CommandInterface {};
    }

}
