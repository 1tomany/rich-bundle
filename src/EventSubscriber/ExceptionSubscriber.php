<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Exception\WrappedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    use RequestInspectorTrait;

    public function __construct(private SerializerInterface $serializer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', -1],
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

        $wrapped = new WrappedException(...[
            'exception' => $event->getThrowable(),
        ]);

        $content = $this->serializer->serialize($wrapped, $format, [
            'exception' => $event->getThrowable(),
        ]);

        $response = new Response($content, $wrapped->getStatus(), $wrapped->getHeaders() + [
            'Content-Type' => $event->getRequest()->getMimeType($format),
        ]);

        $event->setResponse($response);
    }
}
