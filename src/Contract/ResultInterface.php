<?php

namespace OneToMany\RichBundle\Contract;

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
     * @param int<100, 599> $status
     */
    public function asStatus(int $status): static;

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): static;

    /**
     * @param list<non-empty-string> $groups
     */
    public function withGroups(array $groups): static;

    /**
     * @param list<array<string, string>> $headers
     */
    public function withHeaders(array $headers): static;
}
