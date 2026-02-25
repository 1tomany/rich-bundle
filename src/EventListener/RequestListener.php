<?php

namespace OneToMany\RichBundle\EventListener;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use OneToMany\RichBundle\Error\HttpError;
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

use function array_filter;
use function array_map;
use function bin2hex;
use function get_debug_type;
use function implode;
use function in_array;
use function random_bytes;
use function sprintf;

readonly class RequestListener implements EventSubscriberInterface
{
    public const string REQUEST_ID_KEY = '_rich_request_id';

    /**
     * @param non-empty-list<non-empty-lowercase-string> $acceptFormats
     * @param non-empty-list<non-empty-lowercase-string> $contentTypeFormats
     * @param non-empty-string $serializedApiPrefix
     */
    public function __construct(
        private SerializerInterface $serializer,
        private array $acceptFormats = ['json', 'xml'],
        private array $contentTypeFormats = ['form', 'json'],
        private string $serializedApiPrefix = '/api',
    ) {
    }

    /**
     * @see Symfony\Component\EventDispatcher\EventSubscriberInterface
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [
                ['onKernelRequest', 128],
            ],
            ViewEvent::class => [
                ['onKernelView', 0],
            ],
            ResponseEvent::class => [
                ['onKernelResponse', 0],
            ],
            ExceptionEvent::class => [
                ['onKernelException', 2],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Generate a random request ID for logging
        $event->getRequest()->attributes->set(self::REQUEST_ID_KEY, bin2hex(random_bytes(6)));

        if ($this->isSerializableRequest($event->getRequest())) {
            $format = $event->getRequest()->getPreferredFormat(null);

            if (null !== $format) {
                if (!in_array($format, $this->acceptFormats, true)) {
                    throw HttpException::create(406, sprintf('The server cannot respond with a media type the client will find acceptable. Acceptable media types are: "%s".', $this->flattenFormats($this->acceptFormats)));
                }
            }

            $format = $event->getRequest()->getContentTypeFormat();

            if (null !== $format) {
                if (!in_array($format, $this->contentTypeFormats, true)) {
                    throw HttpException::create(415, sprintf('The server cannot process content with the media type "%s". Supported content media types are: "%s".', $event->getRequest()->getMimeType($format), $this->flattenFormats($this->contentTypeFormats)));
                }
            }
        }
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof ResultInterface) {
            $response = $this->serializeResponse(
                $event->getRequest(),
                $result->getResult(),
                $result->getContext(),
                $result->getStatus(),
                $result->getHeaders(),
            );

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->isSerializableRequest($event->getRequest())) {
            $event->getResponse()->setVary(['Accept']);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->isSerializableRequest($event->getRequest())) {
            // Flatten and normalize the exception
            $httpError = new HttpError($event->getThrowable());

            // Serialize the flattened exception
            $response = $this->serializeResponse(
                $event->getRequest(),
                $httpError,
                $httpError->getContext(),
                $httpError->getStatus(),
                $httpError->getHeaders(),
            );

            $event->setResponse($response);
        }
    }

    private function isSerializableRequest(Request $request): bool
    {
        return 0 === stripos($request->getRequestUri(), $this->serializedApiPrefix);
    }

    /**
     * @param list<non-empty-string> $formats
     */
    private function flattenFormats(array $formats): string
    {
        $mediaTypes = array_map(function (string $type): string {
            return Request::getMimeTypes($type)[0] ?? $type;
        }, $formats);

        return implode('", "', array_filter($mediaTypes));
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    private function serializeResponse(
        Request $request,
        mixed $data,
        array $context = [],
        int $status = Response::HTTP_OK,
        array $headers = [],
    ): Response {
        $format = $this->resolveResponseFormat($request);

        try {
            $content = $this->serializer->serialize($data, $format, $context);
        } catch (SerializerExceptionInterface $e) {
            throw new RuntimeException(sprintf('Serializing the response failed because data of type "%s" could not be encoded as "%s".', get_debug_type($data), $format), previous: $e);
        }

        $response = new Response($content, $status, $headers + [
            'Content-Type' => $request->getMimeType($format),
        ]);

        return $response;
    }

    private function resolveResponseFormat(Request $request): string
    {
        foreach ($this->acceptFormats as $acceptFormat) {
            $format = $request->getPreferredFormat(...[
                'default' => $acceptFormat,
            ]);

            if ($acceptFormat === $format) {
                return $acceptFormat;
            }
        }

        return $this->acceptFormats[0];
    }
}
