<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function sprintf;

class ConsoleError extends HttpError
{
    public function __construct(
        ValidationFailedException $throwable,
    ) {
        if (0 === $throwable->getViolations()->count()) {
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
        return sprintf('The property "%s" is not valid: %s.', $this->getViolations()[0]->property, $this->getViolations()[0]->message);
    }
}
