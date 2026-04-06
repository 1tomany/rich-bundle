<?php

namespace OneToMany\RichBundle\Attribute;

use OneToMany\RichBundle\Exception\InvalidArgumentException;

use function implode;
use function in_array;
use function sprintf;
use function strtolower;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
readonly class SourceRequestBag extends PropertySource
{
    /**
     * @var 'attributes'|'headers'|'query'|'request'|'server'
     */
    public string $type;

    /**
     * @param string|list<non-empty-string>|callable|null $callback
     */
    public function __construct(
        string $type,
        mixed $callback = null,
    ) {
        parent::__construct(null, true, false, $callback);

        $typeLower = strtolower($type);

        if (!in_array($typeLower, $this->getTypes())) {
            throw new InvalidArgumentException(sprintf('The type "%s" is not valid, must be one of: "%s".', $type, implode('", "', $this->getTypes())));
        }

        $this->type = $typeLower;
    }

    /**
     * @return non-empty-list<'attributes'|'headers'|'query'|'request'|'server'>
     */
    public function getTypes(): array
    {
        return ['attributes', 'headers', 'query', 'request', 'server'];
    }
}
