<?php

namespace OneToMany\RichBundle\Contract\Error\Record;

final readonly class StackItem
{
    /**
     * @param class-string $class
     * @param non-negative-int $line
     */
    public function __construct(
        public string $class,
        public string $message,
        public string $file,
        public int $line,
    ) {
    }

    public static function create(\Throwable $throwable): static
    {
        return new static($throwable::class, $throwable->getMessage(), $throwable->getFile(), $throwable->getLine());
    }

    /**
     * @return array{
     *   class: class-string,
     *   message: string,
     *   file: string,
     *   line: non-negative-int,
     * }
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }
}
