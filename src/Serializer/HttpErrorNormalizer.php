<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use OneToMany\RichBundle\Contract\Error\Record\Violation;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function array_merge;

/**
 * @phpstan-import-type Stack from HttpErrorInterface
 */
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
     *   stack?: list<Stack>,
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

        // Expand Violation objects
        $record['violations'] = [];

        foreach ($data->getViolations() as $v) {
            $record['violations'][] = $v->toArray();
        }

        if (true === $this->debug) {
            // Expand Trace objects
            $record['trace'] = [];

            foreach ($data->getTrace() as $t) {
                $record['trace'][] = $t->toArray();
            }

            $record = array_merge($record, [
                'stack' => $data->getStack(),
                // 'trace' => $data->getTrace(),
            ]);
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
