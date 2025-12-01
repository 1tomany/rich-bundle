<?php

namespace OneToMany\RichBundle\Tests\Validator;

use OneToMany\RichBundle\Validator\UninitializedProperties;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ValidatorTests')]
final class UninitializedPropertiesTest extends TestCase
{
    public function testConstructorDoesNotTriggerDeprecationError(): void
    {
        $this->expectNotToPerformAssertions();

        new UninitializedProperties();
    }
}
