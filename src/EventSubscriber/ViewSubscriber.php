<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Controller\ControllerResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class ViewSubscriber implements EventSubscriberInterface
{
    public function __construct(private NormalizerInterface $normalizer)
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
            $normalizedData = $this->normalizer->normalize(
                $result->data, null, $result->context
            );

            $response = new JsonResponse(...[
                'data' => $normalizedData,
                'status' => $result->status,
                'headers' => $result->headers,
            ]);

            $event->setResponse($response);
        }
    }
}
