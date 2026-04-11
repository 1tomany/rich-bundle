<?php

namespace OneToMany\RichBundle\Tests\Error;

use OneToMany\RichBundle\Error\ConsoleError;
use OneToMany\RichBundle\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Group('UnitTests')]
#[Group('ErrorTests')]
final class ConsoleErrorTest extends TestCase
{
    public function testConstructorRequiresAtLeastOneConstraintViolation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The constraint violation list cannot be empty.');

        new ConsoleError(new ValidationFailedException(null, new ConstraintViolationList([])));
    }

    public function testToString(): void
    {
        $violationList = new ConstraintViolationList([
            new ConstraintViolation('Invalid email.', null, [], null, 'email', null),
        ]);

        $consoleError = new ConsoleError(new ValidationFailedException(null, $violationList));

        $this->assertEquals('The property "email" is not valid: invalid email.', $consoleError->__toString());
    }
}
