<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ConsoleError extends HttpError
{
    private ConstraintViolationListInterface $violationList;

    public function __construct(
        ValidationFailedException $throwable,
    ) {
        $this->violationList = $throwable->getViolations();

        if (0 === $this->violationList->count()) {
            throw new InvalidArgumentException('The constraint violation list cannot be empty.');
        }

        parent::__construct($throwable);
    }

    /**
     * @see \Stringable
     *
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return \sprintf('The property "%s" is not valid: %s.', $this->violationList->get(0)->getPropertyPath(), $this->violationList->get(0)->getMessage());
    }
}
