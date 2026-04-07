<?php

namespace OneToMany\RichBundle\Tests\Input\Fixtures\Input;

use OneToMany\RichBundle\Attribute\SourceAttributesBag;
use OneToMany\RichBundle\Attribute\SourceHeadersBag;
use OneToMany\RichBundle\Attribute\SourceQueryBag;
use OneToMany\RichBundle\Attribute\SourceRequestBag;
use OneToMany\RichBundle\Attribute\SourceServerBag;
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
        #[SourceAttributesBag]
        public array $attributes,

        #[SourceHeadersBag]
        public array $headers,

        #[SourceQueryBag]
        public array $query,

        #[SourceRequestBag]
        public array $request,

        #[SourceServerBag]
        public array $server,
    ) {
    }
}
