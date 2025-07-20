<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ViewSubscriber implements EventSubscriberInterface
{
    private const string FORMAT = 'json';

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

        if ($result instanceof ResultInterface) {
            // Attempt to serialize the result value
            $responseContent = $this->serializer->serialize(
                $result(), self::FORMAT, $result->getContext()
            );

            $response = new Response($responseContent, $result->getStatus(), $result->getHeaders() + [
                'Content-Type' => $event->getRequest()->getMimeType(self::FORMAT),
            ]);

            $event->setResponse($response);
        }
    }
}
