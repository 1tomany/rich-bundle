<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class RichPropertyPayload extends RichProperty
{

    public function __construct()
    {
        parent::__construct(PropertySource::Payload);
    }

}
