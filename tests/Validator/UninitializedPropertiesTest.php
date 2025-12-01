<?php

namespace OneToMany\RichBundle\Tests\Validator;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Validator\UninitializedProperties;
use OneToMany\RichBundle\Validator\UninitializedPropertiesValidator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

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
