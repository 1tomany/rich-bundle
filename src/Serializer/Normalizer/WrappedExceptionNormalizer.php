<?php

namespace OneToMany\RichBundle\Serializer\Normalizer;

use OneToMany\RichBundle\Exception\WrappedExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class WrappedExceptionNormalizer implements NormalizerInterface
{
    public function __construct(private bool $debug = false)
    {
    }

    /**
     * @param WrappedExceptionInterface $object
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $record = [
            'status' => $object->getStatus(),
            'title' => $object->getTitle(),
            'detail' => $object->getMessage(),
            'violations' => $object->getViolations(),
            'stack' => $object->getStack(),
        ];

        if (false === $this->debug) {
            unset($record['stack']);
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof WrappedExceptionInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            WrappedExceptionInterface::class => true,
        ];
    }
}
