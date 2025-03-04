<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
readonly class RichProperty
{

    public function __construct(
        public PropertySource $source = PropertySource::Payload,
    )
    {
    }

}
