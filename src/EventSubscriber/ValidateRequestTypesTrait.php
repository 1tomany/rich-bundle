<?php

namespace OneToMany\RichBundle\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

use function array_filter;
use function array_map;
use function implode;
use function in_array;
use function stripos;
use function vsprintf;

// @phpstan-ignore trait.unused
trait ValidateRequestTypesTrait
{
    /**
     * @param list<non-empty-string> $acceptTypes
     * @param list<non-empty-string> $contentTypes
     */
    private function validateRequestTypes(
        Request $request,
        string $uriPrefix = '/api',
        array $acceptTypes = ['json'],
        array $contentTypes = ['form', 'json'],
    ): void {
        if ($this->shouldValidateRequest($request, $uriPrefix)) {
            $contentType = $request->getContentTypeFormat();

            if (null !== $contentType && !in_array($contentType, $contentTypes, true)) {
                $message = vsprintf('The type "%s" is not supported. Supported content types are: "%s".', [
                    $request->getMimeType($contentType), $this->flattenMimeTypes($contentTypes),
                ]);

                throw new UnsupportedMediaTypeHttpException($message);
            }

            /** @var non-empty-string $accept */
            $accept = $request->getPreferredFormat('json');

            if (!in_array($accept, $acceptTypes, true)) {
                $message = vsprintf('The type "%s" is not supported. Acceptable types are: "%s".', [
                    $request->getMimeType($accept), $this->flattenMimeTypes($acceptTypes),
                ]);

                throw new NotAcceptableHttpException($message, headers: ['Vary' => 'Accept']);
            }
        }
    }

    private function shouldValidateRequest(Request $request, string $uriPrefix): bool
    {
        return 0 === stripos($request->getRequestUri(), $uriPrefix);
    }

    /**
     * @param list<non-empty-string> $mimeTypes
     */
    private function flattenMimeTypes(array $mimeTypes): string
    {
        $flattenedTypes = array_map(function (string $type): string {
            return Request::getMimeTypes($type)[0] ?? $type;
        }, $mimeTypes);

        return implode('", "', array_filter($flattenedTypes));
    }
}
