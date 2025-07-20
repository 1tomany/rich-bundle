<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

use function strtolower;

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
        if (($result = $event->getControllerResult()) instanceof ResultInterface) {
            // Determine the response format by inspecting the "Accept" header
            $format = $event->getRequest()->getPreferredFormat('json') ?? 'json';

            // Attempt to serialize the result value
            $responseContent = $this->serializer->serialize(
                $result(), $format, $result->getContext()
            );

            $contentType = $event->getRequest()->getMimeType(...[
                'format' => strtolower($format),
            ]);

            $response = new Response($responseContent, $result->getStatus(), $result->getHeaders() + [
                'Content-Type' => strtolower($contentType ?? 'application/json'),
            ]);

            $event->setResponse($response);
        }
    }
}
