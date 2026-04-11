<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function sprintf;

class ConsoleError extends HttpError
{
    private ConstraintViolationListInterface $violationList;

    public function __construct(
        ValidationFailedException $throwable,
    ) {
        $this->violationList = $throwable->getViolations();

        parent::__construct($throwable);

        if (empty($this->violations)) {
            throw new InvalidArgumentException('The constraint violation list cannot be empty.');
        }
    }

    /**
     * @see \Stringable
     *
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return sprintf('The property "%s" is not valid: %s.', $this->getViolations()[0]->property, $this->getViolations()[0]->message);
    }
}
