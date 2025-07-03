<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Controller\ControllerResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ViewSubscriber implements EventSubscriberInterface
{
    use RequestInspectorTrait;

    public function __construct(private SerializerInterface $serializer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['onKernelView', 0],
            ],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        if (($result = $event->getControllerResult()) instanceof ControllerResponse) {
            $format = $this->getResponseFormat($event->getRequest()) ?? 'json';

            $content = $this->serializer->serialize(
                $result->data, $format, $result->context
            );

            $response = new Response($content, $result->status, $result->headers + [
                'Content-Type' => $event->getRequest()->getMimeType($format),
            ]);

            $event->setResponse($response);
        }
    }
}
