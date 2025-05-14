<?php

namespace OneToMany\RichBundle\Tests\Contract;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ContractTests')]
final class AbstractJsonSchemaTest extends TestCase
{
    public function testGettingNameReturnsShortClassNameByDefault(): void
    {
        $this->assertEquals('TestSchema', new TestSchema()->getName());
    }
}
