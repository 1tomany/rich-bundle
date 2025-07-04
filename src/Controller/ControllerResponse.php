<?php

namespace OneToMany\RichBundle\Controller;

use OneToMany\RichBundle\Controller\Exception\InvalidHttpStatusException;
use OneToMany\RichBundle\Exception\WrappedException;
use OneToMany\RichBundle\Exception\WrappedExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class ControllerResponse
{
    /**
     * @param int<100, 599> $status
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public function __construct(
        public mixed $data,
        public int $status,
        public array $context = [],
        public array $headers = [],
    ) {
        if (!isset(Response::$statusTexts[$status])) {
            throw new InvalidHttpStatusException($status);
        }
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public static function ok(
        mixed $data,
        array $context = [],
        array $headers = [],
    ): self {
        return new self($data, 200, $context, $headers);
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public static function created(
        mixed $data,
        array $context = [],
        array $headers = [],
    ): self {
        return new self($data, 201, $context, $headers);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(
        \Throwable|WrappedExceptionInterface $exception,
        array $context = [],
    ): self {
        if (!$exception instanceof WrappedExceptionInterface) {
            $exception = new WrappedException(...[
                'exception' => $exception,
            ]);
        }

        return new self($exception, $exception->getStatus(), $context + ['exception' => $exception], $exception->getHeaders());
    }
}
