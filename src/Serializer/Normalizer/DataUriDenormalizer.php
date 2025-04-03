<?php

namespace OneToMany\RichBundle\Serializer\Normalizer;

use OneToMany\DataUri\DataUri;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function OneToMany\DataUri\parse_data;

final readonly class DataUriDenormalizer implements DenormalizerInterface
{
    /**
     * @param string|File|null $data
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): DataUri
    {
        return parse_data($data);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return (\is_string($data) || $data instanceof File) && \is_a($type, DataUri::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DataUri::class => true,
        ];
    }
}
