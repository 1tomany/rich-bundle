<?php

namespace OneToMany\RichBundle\EventListener;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use OneToMany\RichBundle\Error\HttpError;
use OneToMany\RichBundle\EventListener\Exception\SerializingResponseFailedException;
use OneToMany\RichBundle\HTTP\ResponseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

use function bin2hex;
use function random_bytes;

readonly class HttpListener
{
    use ResponseTrait;

    public const string DEFAULT_FORMAT = 'json';
    public const string REQUEST_ID_KEY = '_rich_request_id';

    public function __construct(
        private SerializerInterface $serializer,
        // private LoggerInterface $logger,
    ) {
    }

    public function generateRequestId(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set(self::REQUEST_ID_KEY, bin2hex(random_bytes(6)));
    }

    public function renderControllerResult(ViewEvent $event): void
    {
        if (($result = $event->getControllerResult()) instanceof ResultInterface) {
            $event->setResponse($this->generateResponse($event->getRequest(), $this->serializeResponse($event->getRequest(), $result(), $result->getContext()), $result->getStatus(), $result->getHeaders()));
        }
    }
}
