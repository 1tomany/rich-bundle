<?php

namespace OneToMany\RichBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class MissingProperties extends Constraint
{
    public readonly string $message;

    public function __construct(?array $groups = null, $payload = null)
    {
        parent::__construct([], $groups, $payload);

        $this->message = 'The property "{{ property }}" could not be mapped because it was not found in the request and has no default value.';
    }
}
