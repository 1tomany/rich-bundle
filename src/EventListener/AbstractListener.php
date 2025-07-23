<?php

namespace OneToMany\RichBundle\EventListener;

use OneToMany\RichBundle\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract readonly class AbstractListener
{
    /**
     * @return non-empty-list<non-empty-string>
     */
    abstract public function getAcceptFormats(Request $request): array;

    public function getResponseFormat(Request $request): string
    {
        $acceptFormats = $this->getAcceptFormats($request);

        foreach ($acceptFormats as $acceptFormat) {
            $format = $request->getPreferredFormat(...[
                'default' => $acceptFormat,
            ]);

            if ($acceptFormat === $format) {
                return $acceptFormat;
            }
        }

        return $acceptFormats[0];
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    abstract public function getContentFormats(Request $request): array;

    abstract protected function getSerializer(): SerializerInterface;

    protected function validateMediaTypes(Request $request): void
    {
        $format = $request->getPreferredFormat(null);

        if (null !== $format) {
            $acceptFormats = $this->getAcceptFormats($request);

            if (!in_array($format, $acceptFormats, true)) {
                throw new NotAcceptableHttpException(\sprintf('The server cannot respond with a media type the client will find acceptable. Acceptable media types are: "%s".', $this->flattenFormats($acceptFormats)));
            }
        }

        $format = $request->getContentTypeFormat();

        if (null !== $format) {
            $contentFormats = $this->getContentFormats($request);

            if (!in_array($format, $contentFormats, true)) {
                throw new UnsupportedMediaTypeHttpException(\sprintf('The server cannot process content with the media type "%s". Supported content media types are: "%s".', $request->getMimeType($format), $this->flattenFormats($contentFormats)));
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function serializeResponse(Request $request, mixed $data, array $context): string
    {
        try {
            return $this->getSerializer()->serialize($data, $this->getResponseFormat($request), $context);
        } catch (SerializerExceptionInterface $e) {
            throw new RuntimeException(sprintf('Serializing the response failed because data of type "%s" could not be encoded.', \get_debug_type($data)), previous: $e);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    protected function generateResponse(Request $request, string $content, int $status, array $headers): Response
    {
        $format = $this->getResponseFormat($request);

        $response = new Response($content, $status, $headers + [
            'Content-Type' => $request->getMimeType($format),
        ]);

        return $response;
    }

    /**
     * @param list<non-empty-string> $formats
     */
    protected function flattenFormats(array $formats): string
    {
        $mediaTypes = \array_map(function (string $type): string {
            return Request::getMimeTypes($type)[0] ?? $type;
        }, $formats);

        return implode('", "', \array_filter($mediaTypes));
    }
}
