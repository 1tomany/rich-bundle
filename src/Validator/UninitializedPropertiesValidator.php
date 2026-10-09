<?php

namespace OneToMany\RichBundle\Validator;

use OneToMany\RichBundle\Contract\Action\InputInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UninitializedPropertiesValidator extends ConstraintValidator
{
    /**
     * @see Symfony\Component\Validator\ConstraintValidatorInterface
     *
     * @throws UnexpectedTypeException when the value is not an instance of {@see OneToMany\RichBundle\Contract\Action\InputInterface}
     * @throws UnexpectedTypeException when the constraint is not an instance of {@see OneToMany\RichBundle\Validator\UninitializedProperties}
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof InputInterface) {
            throw new UnexpectedTypeException($value, InputInterface::class);
        }

        if (!$constraint instanceof UninitializedProperties) {
            throw new UnexpectedTypeException($constraint, UninitializedProperties::class);
        }

        foreach (new \ReflectionClass($value)->getProperties() as $property) {
            if (!$property->isInitialized($value)) {
                $this->context->buildViolation($constraint->message)->atPath($property->getName())->addViolation();
            }
        }
    }
}
