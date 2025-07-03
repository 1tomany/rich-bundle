<?php

namespace OneToMany\RichBundle\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;

use function in_array;

trait RequestInspectorTrait
{
    /**
     * This determines the response format in the following order:
     *
     *   1. The `format` attribute on the route
     *   2. The HTTP Accept header
     *   3. The HTTP Content-Type header
     *
     * The Content-Type header is inspected last to account for
     * scenarios where an exception is thrown before any app logic
     * can take place. For example, if the client makes a POST
     * request with a JSON payload to a URL that does not exist,
     * Symfony will throw a 404 exception. Because a route wasn't
     * found, the `format` attribute does not exist. We can't always
     * assume the client is using an Accept header, so as a final
     * attempt the Content-Type header is used to get a good idea
     * of the type of content the client would like in response.
     */
    private function getResponseFormat(Request $request): ?string
    {
        $format = $request->getPreferredFormat(
            $request->getContentTypeFormat()
        );

        return in_array($format, ['xml', 'json']) ? $format : null;
    }
}
