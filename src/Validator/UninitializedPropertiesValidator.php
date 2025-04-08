<?php

namespace OneToMany\RichBundle\Validator;

use OneToMany\RichBundle\Contract\InputInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UninitializedPropertiesValidator extends ConstraintValidator
{
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
                $this->context->buildViolation($constraint->message)->atPath($property->name)->addViolation();
            }
        }
    }
}
