<?php

namespace OneToMany\RichBundle\Tests\Validator;

use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use OneToMany\RichBundle\Validator\UninitializedProperties;
use OneToMany\RichBundle\Validator\UninitializedPropertiesValidator;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<UninitializedPropertiesValidator>
 */
#[Group('UnitTests')]
#[Group('ValidatorTests')]
final class UninitializedPropertiesValidatorTest extends ConstraintValidatorTestCase
{
    public function testValidationRequiresInputInterfaceValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "OneToMany\RichBundle\Contract\InputInterface", "string" given');

        $this->validator->validate('string', new UninitializedProperties());
    }

    public function testValidationRequiresMissingPropertiesConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "OneToMany\RichBundle\Validator\UninitializedProperties", "Symfony\Component\Validator\Constraints\IsNull" given');

        $input = new class implements InputInterface {
            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $this->validator->validate($input, new Assert\IsNull());
    }

    public function testObjectWithUninitializedPropertiesIsInvalid(): void
    {
        $input = new class implements InputInterface {
            public string $name;

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $this->validator->validate($input, $constraint = new UninitializedProperties());

        $this->buildViolation($constraint->message)->atPath('property.path.name')->assertRaised();
    }

    public function testObjectWithInitializedPropertiesIsValid(): void
    {
        $input = new class('Vic', 'vcherubini@gmail.com') implements InputInterface {
            public string $name;
            public string $email;

            public function __construct(string $name, string $email)
            {
                $this->name = $name;
                $this->email = $email;
            }

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $this->validator->validate($input, new UninitializedProperties());

        $this->assertNoViolation();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new UninitializedPropertiesValidator();
    }
}
