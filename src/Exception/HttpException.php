<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Contract\Exception\ExceptionInterface;

class HttpException extends \Symfony\Component\HttpKernel\Exception\HttpException implements ExceptionInterface
{
    /**
     * @param array<string, string> $headers
     */
    public static function create(int $statusCode, string $message = '', ?\Throwable $previous = null, array $headers = []): self
    {
        return new self($statusCode, $message, $previous, $headers, $statusCode);
    }
}
