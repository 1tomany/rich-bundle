<?php

namespace OneToMany\RichBundle\Serializer\Normalizer;

use OneToMany\DataUri\SmartFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function is_a;
use function is_bool;
use function is_string;
use function OneToMany\DataUri\parse_data;

final readonly class SmartFileDenormalizer implements DenormalizerInterface
{
    /**
     * @param string|File|null $data
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SmartFile
    {
        $tempDir = $context['temp_dir'] ?? null;

        if (!is_string($tempDir)) {
            $tempDir = null;
        }

        $hashAlgorithm = $context['hash_algorithm'] ?? null;

        if (!is_string($hashAlgorithm)) {
            $hashAlgorithm = 'sha256';
        }

        $selfDestruct = $context['self_destruct'] ?? null;

        if (!is_bool($selfDestruct)) {
            $selfDestruct = true;
        }

        return parse_data($data, $tempDir, $hashAlgorithm, $selfDestruct);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return (is_string($data) || $data instanceof File) && is_a($type, SmartFile::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SmartFile::class => true,
        ];
    }
}
