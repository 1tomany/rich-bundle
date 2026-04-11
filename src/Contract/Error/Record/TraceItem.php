<?php

namespace OneToMany\RichBundle\Contract\Error\Record;

use function max;

final readonly class TraceItem
{
    /**
     * @param ?class-string $class
     * @param ?non-negative-int $line
     */
    public function __construct(
        public ?string $class,
        public string $function,
        public ?string $file,
        public ?int $line,
    ) {
    }

    /**
     * @param array{
     *   class?: class-string,
     *   function: string,
     *   file?: string,
     *   line?: int,
     * } $trace
     */
    public static function create(array $trace): static
    {
        return new static($trace['class'] ?? null, $trace['function'], $trace['file'] ?? null, isset($trace['line']) ? max(0, $trace['line']) : null);
    }

    /**
     * @return array{
     *   class: ?class-string,
     *   function: ?string,
     *   file: ?string,
     *   line: ?non-negative-int,
     * }
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'function' => $this->function,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }
}
