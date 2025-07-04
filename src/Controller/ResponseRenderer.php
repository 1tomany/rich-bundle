<?php

namespace OneToMany\RichBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ResponseRenderer
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function render(ControllerResponse $data, string $format): Response
    {
        try {
            $content = $this->serializer->serialize(
                $data->data, $format, $data->context
            );

            $response = new Response($content, $data->status, $data->headers + [
                'Content-Type' => Request::getMimeTypes($format)[0] ?? $format,
            ]);
        } catch (NotEncodableValueException) {
        }

        return $response;
    }
}
