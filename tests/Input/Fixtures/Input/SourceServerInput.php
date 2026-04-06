<?php

namespace OneToMany\RichBundle\Tests\Input\Fixtures\Input;

use OneToMany\RichBundle\Attribute\SourceServer;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Tests\Input\Fixtures\Command\InputCommand;
use OneToMany\RichBundle\Tests\Input\Fixtures\Input\Trait\ToCommandTrait;

/**
 * @implements InputInterface<InputCommand>
 */
final readonly class SourceServerInput implements InputInterface
{
    use ToCommandTrait;

    public function __construct(
        #[SourceServer]
        public string $request_uri,

        #[SourceServer(name: 'SERVER_NAME')]
        public string $serverName,
    ) {
    }
}
