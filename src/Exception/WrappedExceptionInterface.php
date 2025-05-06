<?php

namespace OneToMany\RichBundle\Exception;

interface WrappedExceptionInterface
{
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
    public function getMessage(): string;

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array;

    /**
     * @return list<array<string, int|string>>
     */
    public function getStack(): array;

    /**
     * @return list<array<string, string>>
     */
    public function getViolations(): array;
}
