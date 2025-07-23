<?php

namespace OneToMany\RichBundle\HTTP;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

use function array_filter;
use function array_map;
use function implode;
use function in_array;
use function sprintf;

trait ValidationTrait // @phpstan-ignore trait.unused
{

    /**
     * @return list<non-empty-string>
     */
    private function getAcceptableTypes(): array
    {
        return ['json'];
    }

    private function validateAcceptTypes(Request $request): void
    {
        $format = $request->getPreferredFormat(null);

        if (!$format) {
            return;
        }

        if (!in_array($format, $this->getAcceptableTypes(), true)) {
            throw new NotAcceptableHttpException(sprintf('The server cannot respond with a media type the client will find acceptable. Acceptable media types are: "%s".', $this->flattenMediaTypes($this->getAcceptableTypes())));
        }
    }

    /**
     * @param list<non-empty-string> $types
     */
    private function validateContentType(Request $request, array $types = ['form', 'json']): void
    {
        $format = $request->getContentTypeFormat();

        if (!$format) {
            return;
        }

        if (!in_array($format, $types, true)) {
            throw new UnsupportedMediaTypeHttpException(sprintf('The server cannot process content with the media type "%s". Supported content media types are: "%s".', $request->getMimeType($format), $this->flattenMediaTypes($types)));
        }
    }

    /**
     * @param list<non-empty-string> $mediaTypes
     */
    private function flattenMediaTypes(array $mediaTypes): string
    {
        $flattenedTypes = array_map(function (string $type): string {
            return Request::getMimeTypes($type)[0] ?? $type;
        }, $mediaTypes);

        return implode('", "', array_filter($flattenedTypes));
    }
}
