<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Fixture;

use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;

/**
 * @implements InputInterface<CommandInterface>
 */
final class SourceRequestInput implements InputInterface
{
    public function __construct(
        #[SourceRequest]
        private(set) public string $name,

        #[SourceRequest]
        private(set) public int $age,

        #[SourceRequest]
        private(set) public string $email,
    )
    {
    }

    public function toCommand(): CommandInterface
    {
        return new class implements CommandInterface {};
    }
}
