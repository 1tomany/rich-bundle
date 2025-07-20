<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
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
                ['renderResultResponse', 0],
            ],
        ];
    }

    public function renderResultResponse(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if (!$result instanceof ResultInterface) {
            return;
        }

        $data = $this->render($result, $event->getRequest()->getPreferredFormat('json'));

        /*
        $event->setResponse(new JsonResponse($data, $result->getStatus(), $result->getHeaders(), true));
        */
    }

    private function render(ResultInterface $result, ?string $format): string
    {
        return $this->serializer->serialize($result(), $format ?? 'json', $result->getContext());
    }
}
