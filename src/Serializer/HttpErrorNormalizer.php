<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class HttpErrorNormalizer implements NormalizerInterface
{
    public function __construct(private bool $debug = false)
    {
    }

    /**
     * @param HttpErrorInterface $data
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $record = [
            'status' => $data->getStatus(),
            'title' => $data->getTitle(),
            'type' => $data->getType(),
            'detail' => $data->getMessage(),
            'violations' => $data->getViolations(),
            'stack' => $data->getStack(),
            'trace' => $data->getTrace(),
        ];

        if (false === $this->debug) {
            unset($record['stack']);
            unset($record['trace']);
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof HttpErrorInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            HttpErrorInterface::class => true,
        ];
    }
}
