<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Exception;

use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedDecodingContentFailedException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
#[Group('ExceptionTests')]
final class ResolutionFailedDecodingContentFailedExceptionTest extends TestCase
{
    public function testGettingStatusCode(): void
    {
        $this->assertEquals(400, new ResolutionFailedDecodingContentFailedException('xml', null)->getStatusCode());
    }
}
