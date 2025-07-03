<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Controller\ControllerResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ViewSubscriber implements EventSubscriberInterface
{
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
        $result = $event->getControllerResult();

        if ($result instanceof ControllerResponse) {
            $format = $event->getRequest()->getPreferredFormat(
                $event->getRequest()->getContentTypeFormat()
            );

            $json = $this->serializer->serialize(
                $result->data, $format ?? 'json', $result->context
            );

            $response = JsonResponse::fromJsonString(
                $json, $result->status, $result->headers,
            );

            $event->setResponse($response);
        }
    }
}
