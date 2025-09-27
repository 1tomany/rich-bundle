<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\DataUri\SmartFile;
use OneToMany\RichBundle\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Exception\LogicException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function class_exists;
use function is_a;
use function is_string;
use function str_starts_with;

final readonly class SmartFileDenormalizer implements DenormalizerInterface
{
    /**
     * @param string|File $data
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SmartFile // @phpstan-ignore-line
    {
        if ($data instanceof UploadedFile) {
            if (!$data->isValid()) {
                throw new InvalidArgumentException($data->getErrorMessage());
            }

            $name = $data->getClientOriginalName();
        }

        if (is_string($data)) {
            $isHttpUrl = \filter_var($data, \FILTER_VALIDATE_URL) && 0 === \stripos($data, 'http');

            if (!$isHttpUrl && !str_starts_with($data, 'data:')) {
                return \OneToMany\DataUri\parse_text_data($data, null); // @phpstan-ignore-line
            }
        }

        return \OneToMany\DataUri\parse_data($data, $name ?? null); // @phpstan-ignore-line
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!class_exists(SmartFile::class)) {
            throw new LogicException('The SmartFile can not be denormalized because the Data URI library is not installed. Try running "composer require 1tomany/data-uri".');
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
