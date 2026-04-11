<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use OneToMany\RichBundle\Contract\Error\Record\Violation;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function array_merge;

/**
 * @phpstan-import-type Stack from HttpErrorInterface
 * @phpstan-import-type Trace from HttpErrorInterface
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
     *   violations: list<
     *     array{
     *       property: string,
     *       message: string,
     *     },
     *   >,
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
            'violations' => [],
            // 'violations' => $data->getViolations(),
        ];

        foreach ($data->getViolations() as $v) {
            $record['violations'][] = $v->toArray();
        }
        // $mapper = function (Violation $v): array {
        //     return $v->toArray();
        // };

        // $record['violations'] = \array_map($mapper, $data->getViolations());

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
