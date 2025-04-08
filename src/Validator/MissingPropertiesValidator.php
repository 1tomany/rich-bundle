<?php

namespace OneToMany\RichBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class MissingPropertiesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MissingProperties) {
            throw new UnexpectedTypeException($constraint, MissingProperties::class);
        }

        if (!\is_array($value) || !\array_is_list($value)) {
            throw new UnexpectedValueException($value, 'list<string>');
        }

        foreach ($value as $property) {
            if (\is_string($property) && !empty($property)) {
                $this->context->buildViolation($constraint->message)->setParameter('{{ property }}', $property)->atPath($property)->addViolation();
            }
        }
    }
}
