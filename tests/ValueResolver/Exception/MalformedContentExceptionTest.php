<?php

namespace OneToMany\RichBundle\Tests\ValueResolver\Exception;

use OneToMany\RichBundle\ValueResolver\Exception\MalformedRequestContentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
#[Group('ExceptionTests')]
final class MalformedContentExceptionTest extends TestCase
{
    public function testGettingCode(): void
    {
        $this->assertEquals(400, (new MalformedRequestContentException(null))->getCode());
    }
}
