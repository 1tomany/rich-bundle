<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Exception;

use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedMappingRequestFailedException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
#[Group('ExceptionTests')]
final class ResolutionFailedMappingRequestFailedExceptionTest extends TestCase
{
    public function testGettingStatusCode(): void
    {
        $this->assertEquals(400, new ResolutionFailedMappingRequestFailedException()->getStatusCode());
    }
}
