<?php

namespace OneToMany\RichBundle\Serializer;

use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\RichBundle\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Exception\LogicException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function filter_var;
use function interface_exists;
use function is_a;
use function is_string;
use function str_starts_with;
use function stripos;

use const FILTER_VALIDATE_URL;

final readonly class SmartFileDenormalizer implements DenormalizerInterface
{
    /**
     * @param string|File $data
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SmartFileInterface // @phpstan-ignore-line
    {
        if ($data instanceof UploadedFile) {
            if (!$data->isValid()) {
                throw new InvalidArgumentException($data->getErrorMessage());
            }

            $name = $data->getClientOriginalName();
        }

        if (is_string($data)) {
            // @see https://github.com/1tomany/rich-bundle/issues/66
            $isHttpUrl = false !== filter_var($data, FILTER_VALIDATE_URL) && 0 === stripos($data, 'http');

            // The data is not an HTTP URL or a "data:" URI
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
        if (!interface_exists(SmartFileInterface::class)) {
            throw new LogicException('The data can not be denormalized because the 1tomany/data-uri library is not installed. Try running "composer require 1tomany/data-uri".');
        }

        return (is_string($data) || $data instanceof File) && is_a($type, SmartFileInterface::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SmartFileInterface::class => true, // @phpstan-ignore-line
        ];
    }
}
