<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourcePayload extends PropertySource
{

    public function getSource(): string
    {
        return 'request';
    }

}
