<?php

namespace OneToMany\RichBundle\Exception;

interface WrappedExceptionInterface
{

    public function getStatus(): int;
    public function getTitle(): string;
    public function getMessage(): string;

    /**
     * @return array<string, int|float|string>
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
