<?php

namespace OneToMany\RichBundle\Tests\EventListener;

use OneToMany\RichBundle\EventListener\HttpListener;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
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

        // Assert: Request Attribute Is Missing
        $hasSendAccept = $request->attributes->has(...[
            'key' => HttpListener::KEY_SEND_VARY_ACCEPT,
        ]);

        $this->assertFalse($hasSendAccept);

        // Arrange: Create RequestEvent
        $event = $this->createRequestEvent($request);

        // Act: Validate the Request
        $this->createHttpListener()->validateRequest($event);

        // Assert: Request Has Send Vary Accept Attribute
        $hasSendAccept = $request->attributes->has(...[
            'key' => HttpListener::KEY_SEND_VARY_ACCEPT,
        ]);

        $this->assertTrue($hasSendAccept);
    }

    public function testVaryAcceptHeaderIsNotAddedWhenSendVaryAcceptRequestAttributeIsNotTrue(): void
    {
        // Arrange: Create Request
        $request = new Request(server: [
            'REQUEST_URI' => '/api/index',
        ]);

        // Assert: Request Has No Attributes
        $this->assertCount(0, $request->attributes);

        // Arrange: Create Response
        $response = new Response('', 200, []);

        // Assert: Response Has No Vary Header
        $this->assertFalse($response->hasVary());

        // Arrange: Create ResponseEvent
        $event = $this->createResponseEvent($request, $response);

        // Act: Attempt to Add "Vary: Accept" Response Header
        $this->createHttpListener()->addVaryAcceptHeader($event);

        // Assert: Response Has No Vary Header
        $this->assertFalse($response->hasVary());
    }

    public function testVaryAcceptHeaderIsAddedWhenSendVaryAcceptRequestAttributeIsTrue(): void
    {
        // Arrange: Create Request
        $request = new Request(attributes: [
            HttpListener::KEY_SEND_VARY_ACCEPT => true,
        ]);

        // Assert: Request Has Send Vary Accept Attribute
        $hasSendAccept = $request->attributes->has(...[
            'key' => HttpListener::KEY_SEND_VARY_ACCEPT,
        ]);

        $this->assertTrue($hasSendAccept);

        // Arrange: Create Response
        $response = new Response('', 200, []);

        // Assert: Response Has No Vary Header
        $this->assertFalse($response->hasVary());

        // Arrange: Create ResponseEvent
        $event = $this->createResponseEvent($request, $response);

        // Act: Attempt to Add "Vary: Accept" Response Header
        $this->createHttpListener()->addVaryAcceptHeader($event);

        // Assert: Response Has Vary Header
        $this->assertTrue($response->hasVary());
    }

    private function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createAnonymousKernel(), $request, $requestType);
    }

    private function createResponseEvent(Request $request, Response $response, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent($this->createAnonymousKernel(), $request, $requestType, $response);
    }

    public function createHttpListener(?SerializerInterface $serializer = null, string $apiUriPrefix = '/api'): HttpListener
    {
        return new HttpListener($serializer ?? $this->createAnonymousSerializer(), $apiUriPrefix);
    }

    private function createAnonymousKernel(): HttpKernelInterface
    {
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                throw new \RuntimeException('Not implemented!');
            }
        };

        return $kernel;
    }

    private function createAnonymousSerializer(): SerializerInterface
    {
        $serializer = new class implements SerializerInterface {
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
