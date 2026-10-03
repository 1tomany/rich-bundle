<?php

namespace OneToMany\RichBundle\Tests\Serializer;

use OneToMany\RichBundle\Error\HttpError;
use OneToMany\RichBundle\Serializer\HttpErrorNormalizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('SerializerTests')]
final class HttpErrorNormalizerTest extends TestCase
{
    public function testNormalizingExceptionInDebugEnvironmentIncludesStackKey(): void
    {
        $exception1 = new \Exception('Exception 1');
        $exception2 = new \Exception('Exception 2', previous: $exception1);

        $this->assertNotNull($exception2->getPrevious());

        $record = new HttpErrorNormalizer(true)->normalize(
            new HttpError($exception2), null, [],
        );

        $this->assertArrayHasKey('stack', $record);
    }

    public function testNormalizingExceptionInNonDebugEnvironmentDoesNotIncludeStackKey(): void
    {
        $exception1 = new \Exception('Exception 1');
        $exception2 = new \Exception('Exception 2', previous: $exception1);

        $this->assertNotNull($exception2->getPrevious());

        $record = new HttpErrorNormalizer(false)->normalize(
            new HttpError($exception2), null, [],
        );

        $this->assertArrayNotHasKey('stack', $record);
    }
}
