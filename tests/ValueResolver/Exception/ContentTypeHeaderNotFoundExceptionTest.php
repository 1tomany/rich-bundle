<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Exception;

use OneToMany\RichBundle\ValueResolver\Exception\ContentTypeHeaderNotFoundException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
#[Group('ExceptionTests')]
final class ContentTypeHeaderNotFoundExceptionTest extends TestCase
{
    public function testGettingCode(): void
    {
        $this->assertEquals(422, new ContentTypeHeaderNotFoundException()->getCode());
    }
}
