<?php

namespace OneToMany\RichBundle\Controller;

use OneToMany\RichBundle\Controller\Exception\InvalidHttpStatusException;
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
        if (!array_key_exists($status, Response::$statusTexts)) {
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
}
