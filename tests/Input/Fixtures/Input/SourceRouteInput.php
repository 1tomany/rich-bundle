<?php

namespace OneToMany\RichBundle\Tests\Input\Fixtures\Input;

use OneToMany\RichBundle\Attribute\SourceRoute;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Tests\Input\Fixtures\Command\InputCommand;
use OneToMany\RichBundle\Tests\Input\Fixtures\Input\Trait\ToCommandTrait;

/**
 * @implements InputInterface<InputCommand>
 */
final readonly class SourceRouteInput implements InputInterface
{
    use ToCommandTrait;

    public function __construct(
        #[SourceRoute]
        public int $id,

        #[SourceRoute]
        public string $name,
    ) {
    }
}
