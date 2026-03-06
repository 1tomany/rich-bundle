<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Contract\Exception\ExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;

class HttpException extends SymfonyHttpException implements ExceptionInterface
{
    /**
     * @param array<string, string> $headers
     */
    public static function create(int $statusCode, string $message = '', ?\Throwable $previous = null, array $headers = []): self
    {
        return new self($statusCode, $message, $previous, $headers, $statusCode);
    }
}
