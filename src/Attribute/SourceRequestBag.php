<?php

namespace OneToMany\RichBundle\Attribute;

use OneToMany\RichBundle\Exception\InvalidArgumentException;

use function implode;
use function in_array;
use function sprintf;
use function strtolower;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class SourceRequestBag extends PropertySource
{
    /**
     * @var 'attributes'|'headers'|'query'|'request'|'server'
     */
    public string $source;

    /**
     * @param string|list<non-empty-string>|callable|null $callback
     *
     * @throws InvalidArgumentException when the source is not valid
     */
    public function __construct(
        string $source,
        mixed $callback = null,
    ) {
        parent::__construct(null, true, false, $callback);

        $sourceLower = strtolower($source);

        if (!in_array($sourceLower, $this->getSources())) {
            throw new InvalidArgumentException(sprintf('The source "%s" is not valid, must be one of: "%s".', $source, implode('", "', $this->getSources())));
        }

        $this->source = $sourceLower;
    }

    /**
     * @return non-empty-list<'attributes'|'headers'|'query'|'request'|'server'>
     */
    public function getSources(): array
    {
        return ['attributes', 'headers', 'query', 'request', 'server'];
    }
}
