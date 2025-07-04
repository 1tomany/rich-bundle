<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Controller\ControllerResponse;
use OneToMany\RichBundle\EventSubscriber\Exception\LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

use function sprintf;

// @phpstan-ignore trait.unused
trait RenderJsonResponseTrait
{
    private function renderJsonResponse(Request $request, mixed $payload, string $uriPrefix = '/api'): ?JsonResponse
    {
        if (!isset($this->serializer) || !$this->serializer instanceof SerializerInterface) {
            throw new LogicException(sprintf('The "%s::$serializer property must be an object of type "%s".', static::class, SerializerInterface::class));
        }

        if (!$this->shouldRenderJsonResponse($request, $uriPrefix)) {
            return null;
        }

        if ($payload instanceof \Throwable) {
            $payload = ControllerResponse::error(...[
                'exception' => $payload,
            ]);
        }

        if (!$payload instanceof ControllerResponse) {
            return null;

        }
            $json = $this->serializer->serialize(
                $payload->data, 'json', $payload->context
            );

            return JsonResponse::fromJsonString($json, $payload->status, $payload->headers);

    }

    private function shouldRenderJsonResponse(Request $request, string $uriPrefix = '/api'): bool
    {
        return 0 === stripos($request->getRequestUri(), $uriPrefix);
    }
}
