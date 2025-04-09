<?php

namespace OneToMany\RichBundle\Controller\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

use function sprintf;

#[HasUserMessage]
final class InvalidHttpStatusException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(int $status)
    {
        parent::__construct(sprintf('The HTTP status code "%d" is invalid and can not be used.', $status), 500);
    }
}
