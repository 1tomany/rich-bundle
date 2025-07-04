<?php

namespace OneToMany\RichBundle\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

use function array_filter;
use function array_map;
use function implode;
use function in_array;
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
        array $acceptTypes = ['json'],
        array $contentTypes = ['form', 'json'],
    ): void {
        /** @var non-empty-string $type */
        $type = $request->getPreferredFormat('json');

        if (!in_array($type, $acceptTypes, true)) {
            $message = vsprintf('The type "%s" is not supported. Acceptable types are: "%s".', [
                $request->getMimeType($type), $this->flattenMimeTypes($acceptTypes),
            ]);

            throw new NotAcceptableHttpException($message, headers: ['Vary' => 'Accept']);
        }

        $type = $request->getContentTypeFormat();

        if (null !== $type && !in_array($type, $contentTypes, true)) {
            $message = vsprintf('The type "%s" is not supported. Supported content types are: "%s".', [
                $request->getMimeType($type), $this->flattenMimeTypes($contentTypes),
            ]);

            throw new UnsupportedMediaTypeHttpException($message);
        }
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
