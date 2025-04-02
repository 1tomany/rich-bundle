<?php

namespace OneToMany\RichBundle\Tests\Serializer\Normalizer;

use OneToMany\RichBundle\Exception\WrappedException;
use OneToMany\RichBundle\Serializer\Normalizer\ExceptionNormalizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('SerializerTests')]
#[Group('NormalizerTests')]
final class ExceptionNormalizerTest extends TestCase
{
    public function testNormalizingExceptionInDebugEnvironmentIncludesStack(): void
    {
        $exception1 = new \Exception(...[
            'message' => 'Exception 1',
        ]);

        $exception2 = new \Exception(...[
            'message' => 'Exception 2',
            'previous' => $exception1,
        ]);

        $exception3 = new \Exception(...[
            'message' => 'Exception 3',
            'previous' => $exception2,
        ]);

        $normalizer = new ExceptionNormalizer(true);

        $record = $normalizer->normalize(
            new WrappedException($exception3)
        );

        $this->assertArrayHasKey('stack', $record);
        $this->assertNotEmpty($record['stack']);
    }

    public function testNormalizingExceptionInNonDebugEnvironmentDoesNotIncludeStack(): void
    {
        $exception1 = new \Exception(...[
            'message' => 'Exception 1',
        ]);

        $exception2 = new \Exception(...[
            'message' => 'Exception 2',
            'previous' => $exception1,
        ]);

        $normalizer = new ExceptionNormalizer(false);

        $record = $normalizer->normalize(
            new WrappedException($exception2)
        );

        $this->assertArrayNotHasKey('stack', $record);
    }
}
