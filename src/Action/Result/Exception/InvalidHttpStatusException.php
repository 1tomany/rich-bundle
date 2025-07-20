<?php

namespace OneToMany\RichBundle\Action\Result\Exception;

use OneToMany\RichBundle\Exception\InvalidArgumentException;

use function sprintf;

final class InvalidHttpStatusException extends InvalidArgumentException
{
    public function __construct(int $status, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The HTTP status %d is not valid.', $status), 500, $previous);
    }
}
