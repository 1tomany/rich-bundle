<?php

namespace OneToMany\RichBundle\Controller\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

use function sprintf;

#[HasUserMessage]
final class InvalidHttpStatusException extends InvalidArgumentException
{
    public function __construct(int $status, int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The HTTP status %d is invalid.', $status), $code, $previous);
    }
}
