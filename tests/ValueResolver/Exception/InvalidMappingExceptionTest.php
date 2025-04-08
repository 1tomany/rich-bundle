<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Exception;

use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
#[Group('ExceptionTests')]
final class InvalidMappingExceptionTest extends TestCase
{
    public function testGettingCode(): void
    {
        $this->assertEquals(400, new InvalidMappingException(null)->getCode());
    }
}
