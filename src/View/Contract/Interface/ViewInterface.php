<?php

namespace OneToMany\RichBundle\View\Contract\Interface;

interface ViewInterface
{
    public function getData(): mixed;

    /**
     * @return int<100, 599>
     */
    public function getStatus(): int;

    /**
     * @return non-empty-string
     */
    public function getFormat(): string;

    /**
     * @return ?string
     */
    public function getTemplate(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array;
}
