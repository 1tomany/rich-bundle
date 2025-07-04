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
        $data = $event->getControllerResult();

        if ($data instanceof ControllerResponse) {
            $json = $this->serializer->serialize(
                $data->data, 'json', $data->context
            );

            $response = JsonResponse::fromJsonString(
                $json, $data->status, $data->headers
            );
        }

        if (null !== ($response ?? null)) {
            $event->setResponse($response);
        }
    }
}
