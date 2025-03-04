<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final readonly class RichPropertyQueryString extends RichProperty
{

    public function __construct()
    {
        parent::__construct(PropertySource::QueryString);
    }

}
