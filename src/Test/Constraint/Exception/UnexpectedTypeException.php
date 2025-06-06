<?php

namespace OneToMany\RichBundle\Test\Constraint\Exception;

use function get_debug_type;
use function sprintf;

final class UnexpectedTypeException extends InvalidArgumentException
{
    public function __construct(mixed $value, string $expectedType)
    {
        parent::__construct(sprintf('Expected argument of type "%s", "%s" given.', $expectedType, get_debug_type($value)));
    }
}
