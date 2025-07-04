<?php

namespace OneToMany\RichBundle\EventSubscriber;

use OneToMany\RichBundle\Controller\ControllerResponse;
use OneToMany\RichBundle\EventSubscriber\Exception\LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

use function sprintf;

// @phpstan-ignore trait.unused
trait RenderJsonResponseTrait
{
    private function renderJsonResponse(ControllerResponse $response): Response
    {
        if (!isset($this->serializer) || !$this->serializer instanceof SerializerInterface) {
            throw new LogicException(sprintf('The "%s::$serializer property must be an object of type "%s".', static::class, SerializerInterface::class));
        }

        $data = $this->serializer->serialize(
            $response->data, 'json', $response->context
        );

        return JsonResponse::fromJsonString($data, $response->status, $response->headers);
    }

    private function renderErrorResponse(\Throwable $exception): Response
    {
        return $this->renderJsonResponse(ControllerResponse::error($exception));
    }
}
