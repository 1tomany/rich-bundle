<?php

namespace OneToMany\RichBundle\EventListener;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use OneToMany\RichBundle\Error\HttpError;
use OneToMany\RichBundle\EventListener\Exception\SerializingResponseFailedException;
use OneToMany\RichBundle\HTTP\ValidateRequestTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

use function bin2hex;
use function random_bytes;

readonly class HttpListener
{
    use ValidateRequestTrait;

    public const string KEY_REQUEST_ID = '_rich_request_id';
    public const string KEY_SEND_VARY_ACCEPT = '_rich_send_vary_accept';
    public const string RESPONSE_FORMAT = 'json';

    /**
     * @param non-empty-string $apiUriPrefix
     */
    public function __construct(
        private SerializerInterface $serializer,

        #[Autowire('%rich_bundle.api_uri_prefix%')]
        private string $apiUriPrefix,
    ) {
    }

    #[AsEventListener(priority: 128)]
    public function createRequestId(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set(self::KEY_REQUEST_ID, bin2hex(random_bytes(6)));
    }

    public function validateRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->isApiRequestUri($event->getRequest())) {
            // Send the "Vary: Accept" response header
            $event->getRequest()->attributes->add([
                HttpListener::KEY_SEND_VARY_ACCEPT => true,
            ]);

            $this->validateRequestTypes($event->getRequest());
        }
    }

    #[AsEventListener(priority: 0)]
    public function renderView(ViewEvent $event): void
    {
        if (($result = $result = $event->getControllerResult()) instanceof ResultInterface) {
            $event->setResponse(new JsonResponse($this->serializeResponse($result(), $result->getContext()), $result->getStatus(), $result->getHeaders(), true));
        }
    }

    public function renderException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->isApiRequestUri($event->getRequest())) {
            $httpError = new HttpError($event->getThrowable());

            $data = $this->serializeResponse($httpError, [
                'exception' => $event->getThrowable(),
            ]);

            $event->setResponse(new JsonResponse($data, $httpError->getStatus(), $httpError->getHeaders(), true));
        }
    }

    #[AsEventListener(priority: 0)]
    public function addVaryAcceptHeader(ResponseEvent $event): void
    {
        $sendHeader = $event->getRequest()->attributes->get(...[
            'key' => self::KEY_SEND_VARY_ACCEPT,
        ]);

        if (!$sendHeader) {
            return;
        }

        $event->getResponse()->setVary('Accept');
    }

    private function isApiRequestUri(Request $request): bool
    {
        return 0 === stripos($request->getRequestUri(), $this->apiUriPrefix);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function serializeResponse(mixed $data, array $context): string
    {
        try {
            return $this->serializer->serialize($data, self::RESPONSE_FORMAT, $context);
        } catch (SerializerExceptionInterface $e) {
            throw new SerializingResponseFailedException($data, $e);
        }
    }
}
