<?php

namespace OneToMany\RichBundle\Attribute;

final readonly class SourceDefault extends PropertySource
{
    public function __construct()
    {
        parent::__construct(null, true, false, null);
    }
}
