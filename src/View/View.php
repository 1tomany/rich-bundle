<?php

namespace OneToMany\RichBundle\View;

use OneToMany\RichBundle\View\Contract\Interface\ViewInterface;
use OneToMany\RichBundle\View\Exception\InvalidArgumentException;
use OneToMany\RichBundle\View\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Response;

readonly class View implements ViewInterface
{
    /**
     * @param int<100, 599> $status
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public final function __construct(
        protected mixed $data,
        protected int $status = 200,
        protected array $context = [],
        protected array $headers = [],
    ) {
        if (!isset(Response::$statusTexts[$this->status])) {
            throw new InvalidArgumentException(\sprintf('The HTTP status %d is invalid.', $this->status));
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
        return new static($data, 200, $context, $headers);
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
        return new static($data, 201, $context, $headers);
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getFormat(): string
    {
        throw new RuntimeException('Not implemented!');
    }

    public function getTemplate(): ?string
    {
        if (\is_string($this->data)) {
            return $this->data;
        }

        return null;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
