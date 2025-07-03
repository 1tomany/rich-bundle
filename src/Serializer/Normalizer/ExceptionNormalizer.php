<?php

namespace OneToMany\RichBundle\Serializer\Normalizer;

use OneToMany\RichBundle\Exception\WrappedException;
use OneToMany\RichBundle\Exception\WrappedExceptionInterface;
use OneToMany\RichBundle\Serializer\Normalizer\Exception\InvalidArgumentException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function sprintf;

readonly class ExceptionNormalizer implements NormalizerInterface
{
    public function __construct(private bool $debug = false)
    {
    }

    /**
     * @param WrappedExceptionInterface|FlattenException $object
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if (!$object instanceof WrappedExceptionInterface) {
            if (!isset($context['exception']) || !$context['exception'] instanceof \Throwable) {
                throw new InvalidArgumentException(sprintf('Normalizing an object of type "%s" requires the context to have a key named "exception" that implements "%s".', FlattenException::class, \Throwable::class));
            }

            $object = new WrappedException($context['exception']);
        }

        $record = [
            'type' => 'about:blank',
            'title' => $object->getTitle(),
            'status' => $object->getStatus(),
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
        return $data instanceof WrappedExceptionInterface || $data instanceof FlattenException;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FlattenException::class => true,
            WrappedExceptionInterface::class => true,
        ];
    }
}
