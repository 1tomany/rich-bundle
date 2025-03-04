<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class RichProperty
{

    public function __construct(public PropertySource $source = PropertySource::RequestContent)
    {
    }

}
