<?php

namespace OneToMany\RichBundle\Tests\Input\Fixtures;

use OneToMany\RichBundle\Attribute\SourceServer;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Tests\Input\Fixtures\Trait\ToCommandTrait;

final readonly class SourceServerInput implements InputInterface
{
    use ToCommandTrait;

    public function __construct(
        #[SourceServer()]
        public string $request_uri,

        #[SourceServer(name: 'SERVER_NAME')]
        public string $serverName,
    ) {
    }
}
