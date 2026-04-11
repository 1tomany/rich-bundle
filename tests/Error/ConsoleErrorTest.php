<?php

namespace OneToMany\RichBundle\Tests\Error;

use OneToMany\RichBundle\Error\ConsoleError;
use OneToMany\RichBundle\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

#[Group('UnitTests')]
#[Group('ErrorTests')]
final class ConsoleErrorTest extends TestCase
{
    public function testConstructorRequiresAtLeastOneConstraintViolation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The constraint violation list cannot be empty.');

        new ConsoleError(new ConstraintViolationList([]));
    }

    public function testGettingMessageWithEmptyProperty(): void
    {
        $violationList = new ConstraintViolationList([
            new ConstraintViolation('Invalid email.', null, [], null, null, null),
        ]);

        $this->assertEquals('Invalid email.', new ConsoleError($violationList)->getMessage());
    }

    public function testGettingMessageWithNonEmptyProperty(): void
    {
        $violationList = new ConstraintViolationList([
            new ConstraintViolation('Invalid email.', null, [], null, 'email', null),
        ]);

        $this->assertEquals('The property "email" is not valid: invalid email.', new ConsoleError($violationList)->getMessage());
    }
}
