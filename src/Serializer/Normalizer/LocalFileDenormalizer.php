<?php

namespace OneToMany\RichBundle\Serializer\Normalizer;

use OneToMany\DataUri\LocalFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function is_a;
use function is_string;
use function OneToMany\DataUri\parse_data;

final readonly class LocalFileDenormalizer implements DenormalizerInterface
{
    /**
     * @param string|File|null $data
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): LocalFile
    {
        return parse_data($data);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return (is_string($data) || $data instanceof File) && is_a($type, LocalFile::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            LocalFile::class => true,
        ];
    }
}
