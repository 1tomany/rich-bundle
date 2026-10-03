<?php

namespace OneToMany\RichBundle\Contract\Error\Record;

use function get_class;

final readonly class StackError implements \JsonSerializable
{
    public int|string $code;
    public string $message;
    public string $class;
    public string $file;
    public int $line;
    public ?self $previous;

    public function __construct(
        \Throwable $throwable,
    ) {
        $this->code = $throwable->getCode();
        $this->message = $throwable->getMessage();
        $this->class = get_class($throwable);
        $this->file = $throwable->getFile();
        $this->line = $throwable->getLine();

        if ($previous = $throwable->getPrevious()) {
            $previous = new self($previous);
        }

        $this->previous = $previous;
    }

    /**
     * @see \JsonSerializable
     *
     * @return array{
     *   code: int|string,
     *   message: string,
     *   class: string,
     *   file: string,
     *   line: int,
     *   previous: ?self,
     * }
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'class' => $this->class,
            'file' => $this->file,
            'line' => $this->line,
            'previous' => $this->previous,
        ];
    }
}
