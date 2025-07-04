<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Controller\ControllerResponse;
use OneToMany\RichBundle\Controller\ResponseRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ViewSubscriber implements EventSubscriberInterface
{
    use RequestInspectorTrait;

    public function __construct(private ResponseRenderer $responseRenderer)
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
            $format = $this->getResponseFormat(
                $event->getRequest(), 'json'
            );

            $event->setResponse($this->responseRenderer->render($result, $format));
        }
    }
}
