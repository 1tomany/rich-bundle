<?php

namespace OneToMany\RichBundle\HTTP;

use OneToMany\RichBundle\Exception\LogicException;
use OneToMany\RichBundle\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

use function get_debug_type;
use function sprintf;

trait ResponseTrait // @phpstan-ignore trait.unused
{
    public const string DEFAULT_FORMAT = 'json';

    /**
     * @param array<string, mixed> $context
     */
    private function serializeResponse(Request $request, mixed $data, array $context): string
    {
        if (!($this->serializer ?? null) instanceof SerializerInterface) {
            throw new LogicException(sprintf('You must provide a "%s" instance in the "%s::$serializer" property, but that property has not been initialized yet.', SerializerInterface::class, static::class));
        }

        $format = $request->getPreferredFormat(null) ?? self::DEFAULT_FORMAT;

        try {
            return $this->serializer->serialize($data, $format, $context);
        } catch (SerializerExceptionInterface $e) {
            throw new RuntimeException(sprintf('Serializing the response failed because data of type "%s" could not be encoded.', get_debug_type($data)), previous: $e);
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
