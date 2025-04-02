<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Exception\WrappedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var array{json: non-empty-string, xml: non-empty-string}
     */
    private array $responseFormats;

    public function __construct(
        private SerializerInterface $serializer,
        // private NormalizerInterface $normalizer,
    )
    {
        $this->responseFormats = [
            'json' => 'application/json',
            'xml' => 'application/xml',
        ];
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
        $request = $event->getRequest();

        if ($format = $this->getResponseFormat($request)) {
            // Wrap to Prevent Information Leakage
            $e = new WrappedException($event->getThrowable());

            // Create the Response
            $response = new Response(...[
                'status' => $e->getStatus(),
            ]);

            // Generate the Response Body Content
            $content = $this->serializer->serialize($e, $format, [
                'exception' => $event->getThrowable(),
            ]);

            $response->setContent($content);

            // Resolve the Response Content-Type Header
            $response->headers->replace(array_merge($e->getHeaders(), [
                'Content-Type' => $request->getMimeType($format),
            ]));

            $event->setResponse($response);
        }
    }

    private function getResponseFormat(Request $request): ?string
    {
        $format = $request->getPreferredFormat(null);

        if (!$format) {
            return null;
        }

        return array_key_exists($format, $this->responseFormats) ? $format : null;
    }
}
