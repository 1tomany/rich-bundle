<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use OneToMany\RichBundle\Contract\Error\Record\DebugError;
use OneToMany\RichBundle\Contract\Error\Record\Violation;
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
     *   status: int<100,599>,
     *   title: non-empty-string,
     *   message: non-empty-string,
     *   violations: list<Violation>,
     *   stack?: DebugError,
     * }
     */
    #[\Override]
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array {
        $error = $data->jsonSerialize();

        if (true === $this->debug) {
            $error['stack'] = new DebugError(...[
                'throwable' => $data->getThrowable(),
            ]);
        }

        return $error;
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
