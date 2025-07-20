<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\DataUri\SmartFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function class_exists;
use function is_a;
use function is_string;

final readonly class SmartFileDenormalizer implements DenormalizerInterface
{
    /**
     * @param string|File $data
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SmartFile // @phpstan-ignore-line
    {
        if ($data instanceof UploadedFile) {
            $name = $data->getClientOriginalName();
        }

        return \OneToMany\DataUri\parse_data($data, name: $name ?? null); // @phpstan-ignore-line
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!class_exists(SmartFile::class)) {
            throw new \LogicException('The SmartFile can not be denormalized because the Data URI library is not installed. Try running "composer require 1tomany/data-uri".');
        }

        return (is_string($data) || $data instanceof File) && is_a($type, SmartFile::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SmartFile::class => true, // @phpstan-ignore-line
        ];
    }
}
