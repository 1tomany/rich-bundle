<?php

namespace OneToMany\RichBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

readonly class RequestListener
{
    public const string REQUEST_ID_KEY = '_rich_request_id';

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set(self::REQUEST_ID_KEY, bin2hex(random_bytes(6)));
    }
}
