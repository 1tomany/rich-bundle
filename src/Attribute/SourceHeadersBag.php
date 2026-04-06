<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceHeadersBag extends SourceParameterBag
{
    /**
     * @param string|list<non-empty-string>|callable|null $callback
     */
    public function __construct(mixed $callback = null)
    {
        parent::__construct('headers', $callback);
    }
}
