<?php

namespace OneToMany\RichBundle\EventListener\Exception;

use OneToMany\RichBundle\Exception\RuntimeException;

use function get_debug_type;
use function sprintf;

final class SerializingResponseFailedException extends RuntimeException
{
    public function __construct(mixed $data, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Serializing the response failed because data of type "%s" could not be encoded.', get_debug_type($data)), 500, $previous);
    }
}
