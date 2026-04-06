<?php

namespace OneToMany\RichBundle\Tests\Input\Fixtures\Input;

use OneToMany\RichBundle\Attribute\SourceRequestBag;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Tests\Input\Fixtures\Command\InputCommand;
use OneToMany\RichBundle\Tests\Input\Fixtures\Input\Trait\ToCommandTrait;

/**
 * @implements InputInterface<InputCommand>
 */
final readonly class SourceRequestBagInput implements InputInterface
{
    use ToCommandTrait;

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $server
     */
    public function __construct(
        #[SourceRequestBag('attributes')]
        public array $attributes,

        #[SourceRequestBag('headers')]
        public array $headers,

        #[SourceRequestBag('query')]
        public array $query,

        #[SourceRequestBag('request')]
        public array $request,

        #[SourceRequestBag('server')]
        public array $server,
    ) {
    }
}
