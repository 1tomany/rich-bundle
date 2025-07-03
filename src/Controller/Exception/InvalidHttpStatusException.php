<?php

namespace OneToMany\RichBundle\Controller\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

use function sprintf;

#[HasUserMessage]
final class InvalidHttpStatusException extends InvalidArgumentException
{
    public function __construct(int $status)
    {
        parent::__construct(sprintf('HTTP status code %d is invalid.', $status), 500);
    }
}
