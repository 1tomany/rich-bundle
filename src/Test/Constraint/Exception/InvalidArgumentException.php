<?php

namespace OneToMany\RichBundle\Test\Constraint\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
