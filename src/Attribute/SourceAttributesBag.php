<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceAttributesBag extends SourceParameterBag
{
    /**
     * @param string|list<non-empty-string>|callable|null $callback
     */
    public function __construct(mixed $callback = null)
    {
        parent::__construct('attributes', $callback);
    }
}
