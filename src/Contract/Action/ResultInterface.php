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

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array;

    /**
     * @param int<100, 599> $status
     */
    public function withStatus(int $status): static;

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): static;

    /**
     * @param list<non-empty-string> $groups
     */
    public function withGroups(array $groups): static;

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): static;
}
