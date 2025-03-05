<?php

namespace OneToMany\RichBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SourceContainer extends PropertySource
{

    public function getSource(): string
    {
        return 'container';
    }

}
