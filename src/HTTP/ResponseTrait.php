<?php

namespace OneToMany\RichBundle\HTTP;

use OneToMany\RichBundle\HTTP\Exception\SerializingResponseFailedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

trait ResponseTrait // @phpstan-ignore trait.unused
{
    public const string DEFAULT_FORMAT = 'json';

    /**
     * @param array<string, mixed> $context
     */
    private function serializeResponse(Request $request, mixed $data, array $context): string
    {
        if (!($this->serializer ?? null) instanceof SerializerInterface) {
            throw new \RuntimeException('no serializer');
        }

        $format = $request->getPreferredFormat(null) ?? self::DEFAULT_FORMAT;

        try {
            return $this->serializer->serialize($data, $format, $context);
        } catch (SerializerExceptionInterface $e) {
            throw new SerializingResponseFailedException($data, $e);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function generateResponse(Request $request, string $content, int $status, array $headers): Response
    {
        $format = $request->getPreferredFormat(null) ?? self::DEFAULT_FORMAT;

        $response = new Response($content, $status, $headers + [
            'Content-Type' => $request->getMimeType($format),
        ]);

        return $response;
    }
}
