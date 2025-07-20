<?php

namespace OneToMany\RichBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['addVaryAcceptHeader', 0],
            ],
        ];
    }

    public function addVaryAcceptHeader(ResponseEvent $event): void
    {
        if (false === $event->getResponse()->hasVary()) {
            $event->getResponse()->setVary('Accept');
        }
    }
}
