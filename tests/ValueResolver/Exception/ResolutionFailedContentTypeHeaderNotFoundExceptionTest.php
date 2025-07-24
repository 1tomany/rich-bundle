<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Exception;

use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedContentTypeHeaderNotFoundException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
#[Group('ExceptionTests')]
final class ResolutionFailedContentTypeHeaderNotFoundExceptionTest extends TestCase
{
    public function testGettingStatusCode(): void
    {
        $this->assertEquals(422, new ResolutionFailedContentTypeHeaderNotFoundException()->getStatusCode());
    }
}
