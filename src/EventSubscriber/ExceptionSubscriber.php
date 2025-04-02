<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Exception\WrappedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

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

    private function getResponseFormat(Request $request): ?string
    {
        $format = $request->getPreferredFormat(null);

        if (!$format) {
            return null;
        }

        return in_array($format, $this->responseFormats, true) ? $format : null;
    }
}
