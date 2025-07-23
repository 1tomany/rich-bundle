<?php

namespace OneToMany\RichBundle\EventListener;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use OneToMany\RichBundle\HTTP\ResponseTrait;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\SerializerInterface;

readonly class ViewListener
{
    use ResponseTrait;

    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function onKernelView(ViewEvent $event): void
    {
        if (($result = $event->getControllerResult()) instanceof ResultInterface) {
            $event->setResponse($this->generateResponse($event->getRequest(), $this->serializeResponse($event->getRequest(), $result(), $result->getContext()), $result->getStatus(), $result->getHeaders()));
        }
    }
}
