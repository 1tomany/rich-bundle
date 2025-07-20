<?php

namespace OneToMany\RichBundle\Tests\Serializer;

use OneToMany\RichBundle\Error\HttpError;
use OneToMany\RichBundle\Serializer\HttpErrorNormalizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('SerializerTests')]
#[Group('NormalizerTests')]
final class HttpErrorNormalizerTest extends TestCase
{
    public function testNormalizingExceptionInDebugEnvironmentIncludesStack(): void
    {
        $exception1 = new \Exception('Exception 1');
        $exception2 = new \Exception('Exception 2', previous: $exception1);
        $exception3 = new \Exception('Exception 3', previous: $exception2);

        $record = new HttpErrorNormalizer(true)->normalize(
            new HttpError($exception3)
        );

        $this->assertArrayHasKey('stack', $record);
        $this->assertNotEmpty($record['stack']);
    }

    public function testNormalizingExceptionInNonDebugEnvironmentDoesNotIncludeStack(): void
    {
        $exception1 = new \Exception('Exception 1');
        $exception2 = new \Exception('Exception 2', previous: $exception1);

        $record = new HttpErrorNormalizer(false)->normalize(...[
            'data' => new HttpError($exception2),
        ]);

        $this->assertArrayNotHasKey('stack', $record);
    }
}
