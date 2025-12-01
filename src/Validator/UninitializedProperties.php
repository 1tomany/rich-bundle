<?php

namespace OneToMany\RichBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class UninitializedProperties extends Constraint
{
    public readonly string $message;

    public function __construct(?array $groups = null, $payload = null)
    {
        parent::__construct(null, $groups, $payload);

        $this->message = 'This property could not be mapped because it was not found in the request and has no default value.';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
