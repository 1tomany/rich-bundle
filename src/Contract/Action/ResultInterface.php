<?php

namespace OneToMany\RichBundle\Contract\Action;

/**
 * @template R
 */
interface ResultInterface
{
    /**
     * @return R
     */
    public function __invoke(): mixed;

    /**
     * @return int<100, 599>
     */
    public function getStatus(): int;

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;

    // public function getHeaders(): array;

    /**
     * @param int<100, 599> $status
     */
    public function withStatus(int $status): static;

    /**
     * @param list<non-empty-string> $groups
     */
    public function withGroups(array $groups): static;
}
