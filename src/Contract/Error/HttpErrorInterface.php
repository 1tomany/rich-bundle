<?php

namespace OneToMany\RichBundle\Contract\Error;

use OneToMany\RichBundle\Contract\Error\Record\StackItem;
use OneToMany\RichBundle\Contract\Error\Record\TraceItem;
use OneToMany\RichBundle\Contract\Error\Record\Violation;

interface HttpErrorInterface extends \JsonSerializable, \Stringable
{
    public function getThrowable(): \Throwable;

    public function getPrevious(): ?self;

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
     * @return list<StackItem>
     */
    public function getStack(): array;

    /**
     * @return list<TraceItem>
     */
    public function getTrace(): array;

    public function getLogLevel(): string;

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * @see \JsonSerializable
     *
     * @return array{
     *   status: int<100,599>,
     *   title: non-empty-string,
     *   message: non-empty-string,
     *   violations: list<Violation>,
     *   previous: ?HttpErrorInterface,
     * }
     */
    #[\Override]
    public function jsonSerialize(): array;
}
