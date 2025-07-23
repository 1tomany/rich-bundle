<?php

namespace OneToMany\RichBundle\Attribute;

use OneToMany\RichBundle\Contract\Enum\ErrorType;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class HasErrorType
{
    public function __construct(public ErrorType $type = ErrorType::Logic)
    {
    }
}
