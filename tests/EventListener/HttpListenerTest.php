<?php

namespace OneToMany\RichBundle\Tests\EventListener;

use OneToMany\RichBundle\EventListener\HttpListener;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Group('UnitTests')]
#[Group('EventListenerTests')]
final class HttpListenerTest extends TestCase
{
    public function testValidatingRequestSetsTheSendVaryAcceptRequestAttribute(): void
    {
        // Arrange: Create Request
        $request = new Request(server: [
            'REQUEST_URI' => '/api/index',
        ]);

        // Arrange: Create RequestEvent
        $event = $this->createRequestEvent($request);

        // Assert: Request Attribute Is Missing
        $hasSendAccept = $request->attributes->has(...[
            'key' => HttpListener::KEY_SEND_VARY_ACCEPT,
        ]);

        $this->assertFalse($hasSendAccept);

        // Act: Validate the Request
        $this->createHttpListener()->validateRequest($event);

        // Assert: Request Has Attribute
        $hasSendAccept = $request->attributes->has(...[
            'key' => HttpListener::KEY_SEND_VARY_ACCEPT,
        ]);

        $this->assertTrue($hasSendAccept);
    }

    private function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createAnonymousKernel(), $request, $requestType);
    }

    public function createHttpListener(?SerializerInterface $serializer = null, string $apiUriPrefix = '/api'): HttpListener
    {
        return new HttpListener($serializer ?? $this->createAnonymousSerializer(), $apiUriPrefix);
    }

    private function createAnonymousKernel(): HttpKernelInterface
    {
        $kernel = new class() implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                throw new \RuntimeException('Not implemented!');
            }
        };

        return $kernel;
    }

    private function createAnonymousSerializer(): SerializerInterface
    {
        $serializer = new class() implements SerializerInterface {
            public function serialize(mixed $data, string $format, array $context = []): string
            {
                throw new \RuntimeException('Not implemented!');
            }

            public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
            {
                throw new \RuntimeException('Not implemented!');
            }
        };

        return $serializer;
    }

}
