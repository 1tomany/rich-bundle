<?php

namespace OneToMany\RichBundle\EventListener;

use OneToMany\RichBundle\Contract\Action\ResultInterface;
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

use function array_filter;
use function array_map;
use function get_debug_type;
use function implode;
use function in_array;
use function sprintf;

abstract readonly class AbstractListener
{
    /**
     * @return non-empty-list<non-empty-string>
     */
    abstract public function getAcceptFormats(): array;

    /**
     * @return non-empty-list<non-empty-string>
     */
    abstract public function getContentFormats(): array;

    abstract protected function getSerializer(): SerializerInterface;

    public function getResponseFormat(Request $request): string
    {
        foreach ($this->getAcceptFormats() as $acceptFormat) {
            $format = $request->getPreferredFormat(...[
                'default' => $acceptFormat,
            ]);

            if ($acceptFormat === $format) {
                return $acceptFormat;
            }
        }

        return $this->getAcceptFormats()[0];
    }

    protected function validateMediaTypes(RequestEvent $event): void
    {
        $format = $event->getRequest()->getPreferredFormat(null);

        if (null !== $format) {
            if (!in_array($format, $this->getAcceptFormats(), true)) {
                throw HttpException::create(406, sprintf('The server cannot respond with a media type the client will find acceptable. Acceptable media types are: "%s".', $this->flattenFormats($this->getAcceptFormats())));
            }
        }

        $format = $event->getRequest()->getContentTypeFormat();

        if (null !== $format) {
            if (!in_array($format, $this->getContentFormats(), true)) {
                throw HttpException::create(415, sprintf('The server cannot process content with the media type "%s". Supported content media types are: "%s".', $event->getRequest()->getMimeType($format), $this->flattenFormats($this->getContentFormats())));
            }
        }
    }

    protected function renderView(ViewEvent $event): void
    {
        if (($result = $event->getControllerResult()) instanceof ResultInterface) {
            $event->setResponse($this->serializeResponse($event->getRequest(), $result(), $result->getContext(), $result->getStatus(), $result->getHeaders()));
        }
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    protected function serializeResponse(
        Request $request,
        mixed $data,
        array $context = [],
        int $status = Response::HTTP_OK,
        array $headers = [],
    ): Response {
        $format = $this->getResponseFormat($request);

        try {
            $content = $this->getSerializer()->serialize($data, $format, $context);
        } catch (SerializerExceptionInterface $e) {
            throw new RuntimeException(sprintf('Serializing the response failed because data of type "%s" could not be encoded.', get_debug_type($data)), previous: $e);
        }

        $response = new Response($content, $status, $headers + [
            'Content-Type' => $request->getMimeType($format),
        ]);

        return $response;
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
}
