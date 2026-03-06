<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function array_merge;

/**
 * @phpstan-import-type Stack from \OneToMany\RichBundle\Contract\Error\HttpErrorInterface
 * @phpstan-import-type Trace from \OneToMany\RichBundle\Contract\Error\HttpErrorInterface
 * @phpstan-import-type Violation from \OneToMany\RichBundle\Contract\Error\HttpErrorInterface
 */
final readonly class HttpErrorNormalizer implements NormalizerInterface
{
    public function __construct(private bool $debug = false)
    {
    }

    /**
     * @see Symfony\Component\Serializer\Normalizer\NormalizerInterface
     *
     * @param HttpErrorInterface $data
     *
     * @return array{
     *   status: int<100, 599>,
     *   title: non-empty-string,
     *   detail: non-empty-string,
     *   violations: list<Violation>,
     *   stack?: list<Stack>,
     *   trace?: list<Trace>,
     * }
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $record = [
            'status' => $data->getStatus(),
            'title' => $data->getTitle(),
            'detail' => $data->getMessage(),
            'violations' => $data->getViolations(),
            // 'stack' => $data->getStack(),
            // 'trace' => $data->getTrace(),
        ];

        if ($this->debug) {
            $record = array_merge($record, [
                'stack' => $data->getStack(),
                'trace' => $data->getTrace(),
            ]);
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
