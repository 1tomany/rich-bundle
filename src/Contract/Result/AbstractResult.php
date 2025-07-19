<?php

namespace OneToMany\RichBundle\Contract\Result;

use OneToMany\RichBundle\Contract\ResultInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

use function array_is_list;
use function is_array;

/**
 * @template R
 *
 * @implements ResultInterface<R>
 */
abstract class AbstractResult implements ResultInterface
{
    /**
     * @var int<100, 599>
     */
    public private(set) int $status = Response::HTTP_OK;

    /**
     * @var array<string, mixed>
     */
    public private(set) array $context = [];

    /**
     * @var list<array<string, string>>
     */
    public private(set) array $headers = [];

    /**
     * @param R $result
     */
    public function __construct(public readonly mixed $result)
    {
    }

    /**
     * @return R
     */
    public function __invoke(): mixed
    {
        return $this->result;
    }

    public function asStatus(int $status): static
    {
        if (!isset(Response::$statusTexts[$status])) {
            // throw new InvalidHttpStatusException($status);
        }

        $this->status = $status;

        return $this;
    }

    public function withContext(array $context): static
    {
        $existingGroups = $this->context['groups'] ?? null;

        // Maintain existing groups if the context does not overwrite them
        if (is_array($existingGroups) && array_is_list($existingGroups)) {
            if (is_array($context[AbstractNormalizer::GROUPS] ?? null)) {
                $context[AbstractNormalizer::GROUPS] = $existingGroups;
            }
        }

        $this->context = $context;

        return $this;
    }

    public function withGroups(array $groups): static
    {
        $this->context[AbstractNormalizer::GROUPS] = $groups;

        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }
}
