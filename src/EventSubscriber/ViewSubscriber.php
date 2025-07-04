<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Controller\ControllerResponse;
use OneToMany\RichBundle\Controller\ResponseRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ViewSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::VIEW => [
            //     ['onKernelView', 0],
            // ],
        ];
    }
}
