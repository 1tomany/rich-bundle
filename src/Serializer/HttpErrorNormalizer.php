<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class HttpErrorNormalizer implements NormalizerInterface
{
    public function __construct(
        private bool $debug = false,
    ) {
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
     *   violations: list<
     *     array{
     *       property: string,
     *       message: string,
     *     },
     *   >,
     *   stack?: list<
     *     array{
     *       class: class-string,
     *       message: string,
     *       file: string,
     *       line: non-negative-int,
     *     },
     *   >,
     *   trace?: list<
     *     array{
     *       class: ?class-string,
     *       function: ?string,
     *       file: ?string,
     *       line: ?int,
     *     },
     *   >,
     * }
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array {
        $record = [
            'status' => $data->getStatus(),
            'title' => $data->getTitle(),
            'detail' => $data->getMessage(),
        ];

        // Violation Objects
        $record['violations'] = [];

        foreach ($data->getViolations() as $v) {
            $record['violations'][] = $v->toArray();
        }

        if (true === $this->debug) {
            // StackItem Objects
            $record['stack'] = [];

            foreach ($data->getStack() as $si) {
                $record['stack'][] = $si->toArray();
            }

            // TraceItem Objects
            $record['trace'] = [];

            foreach ($data->getTrace() as $ti) {
                $record['trace'][] = $ti->toArray();
            }
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): bool {
        return $data instanceof HttpErrorInterface;
    }

    /**
     * @see Symfony\Component\Serializer\Normalizer\NormalizerInterface
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            HttpErrorInterface::class => true,
        ];
    }
}
