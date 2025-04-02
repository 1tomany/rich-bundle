<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Fixture;

use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;

/**
 * @implements InputInterface<CommandInterface>
 */
final class NotMappedInput implements InputInterface
{
    #[SourceQuery]
    public string $name {
        set(string $v) => trim($v);
    }

    public function __construct()
    {
    }

    public function toCommand(): CommandInterface
    {
        return new class implements CommandInterface {};
    }
}
