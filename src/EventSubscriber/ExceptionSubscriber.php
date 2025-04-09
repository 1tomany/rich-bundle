<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Exception\WrappedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

use function in_array;

final readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var list<non-empty-string>
     */
    private array $responseFormats;

    public function __construct(private SerializerInterface $serializer)
    {
        $this->responseFormats = ['json', 'xml'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', 64],
            ],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $format = $this->getResponseFormat(...[
            'request' => $event->getRequest(),
        ]);

        if (!$format) {
            return;
        }

        // Prevent Data Leakages
        $e = new WrappedException(
            $event->getThrowable()
        );

        // Create the Response
        $errorResponse = new Response(...[
            'status' => $e->getStatus(),
        ]);

        // Generate the Response Body Content
        $body = $this->serializer->serialize($e, $format, [
            'exception' => $event->getThrowable(),
        ]);

        $errorResponse->setContent($body);

        // Resolve the Response Content-Type Header
        $errorResponse->headers->replace(array_merge($e->getHeaders(), [
            'Content-Type' => $event->getRequest()->getMimeType($format),
        ]));

        $event->setResponse($errorResponse);
    }

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

        if (!$format) {
            return null;
        }

        return in_array($format, $this->responseFormats, true) ? $format : null;
    }
}
