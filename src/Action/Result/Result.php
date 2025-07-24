<?php

namespace OneToMany\RichBundle\Action\Result;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

use function array_key_exists;

/**
 * @template R
 *
 * @implements ResultInterface<R>
 */
class Result implements ResultInterface
{
    /** @var int<100, 599> */
    private int $status = Response::HTTP_OK;

    /** @var array<string, mixed> */
    private array $context = [];

    /** @var list<non-empty-string> */
    private array $groups = [];

    /** @var array<string, string> */
    private array $headers = [];

    /**
     * @param R $result
     */
    public function __construct(private mixed $result)
    {
    }

    /**
     * @template T
     *
     * @param T $result
     *
     * @return self<T>
     */
    public static function ok(mixed $result): self
    {
        return new self($result)->withStatus(Response::HTTP_OK);
    }

    /**
     * @template T
     *
     * @param T $result
     *
     * @return self<T>
     */
    public static function created(mixed $result): self
    {
        return new self($result)->withStatus(Response::HTTP_CREATED);
    }

    /**
     * @return R
     */
    public function __invoke(): mixed
    {
        return $this->result;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContext(): array
    {
        // Merge in the groups if none are explicitly set in the context
        if (!array_key_exists(AbstractNormalizer::GROUPS, $this->context)) {
            $this->context[AbstractNormalizer::GROUPS] = $this->groups;
        }

        return $this->context;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withStatus(int $status): static
    {
        if (!isset(Response::$statusTexts[$status])) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->status = $status;

        return $this;
    }

    public function withContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function withGroups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }
}
