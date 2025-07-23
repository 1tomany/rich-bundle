<?php

namespace OneToMany\RichBundle\EventListener;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use OneToMany\RichBundle\Error\HttpError;
use OneToMany\RichBundle\EventListener\Exception\SerializingResponseFailedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public const string DEFAULT_FORMAT = 'json';
    public const string REQUEST_ID_KEY = '_rich_request_id';

    public function __construct(
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
    )
    {
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

    public function logException(ExceptionEvent $event): void
    {
        $error = new HttpError($event->getThrowable());

        $this->logger->log($error->getLevel(), $event->getThrowable(), [
            'exception' => $event->getThrowable(),
        ]);
    }

    public function renderException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $error = new HttpError($event->getThrowable());

        $content = $this->serializeResponse($event->getRequest(), $error, [
            'exception' => $event->getThrowable(),
        ]);

        $event->setResponse($this->generateResponse($event->getRequest(), $content, $error->getStatus(), $error->getHeaders()));
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function serializeResponse(Request $request, mixed $data, array $context): string
    {
        $format = $request->getPreferredFormat(null) ?? self::DEFAULT_FORMAT;

        try {
            return $this->serializer->serialize($data, $format, $context);
        } catch (SerializerExceptionInterface $e) {
            throw new SerializingResponseFailedException($data, $e);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    protected function generateResponse(Request $request, string $content, int $status, array $headers): Response
    {
        $format = $request->getPreferredFormat(null) ?? self::DEFAULT_FORMAT;

        $response = new Response($content, $status, $headers + [
            'Content-Type' => $request->getMimeType($format),
        ]);

        return $response;
    }
}
