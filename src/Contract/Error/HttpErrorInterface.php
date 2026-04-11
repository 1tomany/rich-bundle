<?php

namespace OneToMany\RichBundle\Contract\Error;

use OneToMany\RichBundle\Contract\Enum\ErrorType;
use OneToMany\RichBundle\Contract\Error\Record\Trace;
use OneToMany\RichBundle\Contract\Error\Record\Violation;

/**
 * @phpstan-type Stack array{
 *   class: string,
 *   message: string,
 *   file: string,
 *   line: int,
 * }
 */
interface HttpErrorInterface extends \Stringable
{
    public function getThrowable(): \Throwable;

    public function getType(): ErrorType;

    /**
     * @return int<100, 599>
     */
    public function getStatus(): int;

    /**
     * @return non-empty-string
     */
    public function getTitle(): string;

    /**
     * @return non-empty-string
     */
    public function getDescription(): string;

    /**
     * @return non-empty-string
     */
    public function getMessage(): string;

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array;

    /**
     * @return list<Violation>
     */
    public function getViolations(): array;

    /**
     * @return list<Stack>
     */
    public function getStack(): array;

    /**
     * @return list<Trace>
     */
    public function getTrace(): array;

    public function getLogLevel(): string;

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;
}
