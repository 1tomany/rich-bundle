<?php

namespace OneToMany\RichBundle\Controller;

final readonly class HandlerResponse
{

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public function __construct(
        public mixed $data,
        public int $status,
        public array $context = [],
        public array $headers = [],
    )
    {
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public static function ok(
        mixed $data,
        array $context = [],
        array $headers = [],
    ): self
    {
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
    ): self
    {
        return new self($data, 201, $context, $headers);
    }

}
