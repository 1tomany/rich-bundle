<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function lcfirst;
use function rtrim;
use function sprintf;

class ConsoleError extends HttpError
{
    /**
     * @throws InvalidArgumentException when the constraint violation list is empty
     */
    public function __construct(
        ConstraintViolationListInterface|ValidationFailedException $throwable,
    ) {
        if ($throwable instanceof ConstraintViolationListInterface) {
            $throwable = new ValidationFailedException(null, $throwable);
        }

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
        return $this->getMessage();
    }

    /**
     * @see OneToMany\RichBundle\Contract\Error\HttpErrorInterface
     */
    #[\Override]
    public function getMessage(): string
    {
        return sprintf('The argument "%s" is not valid: %s.', $this->getViolations()[0]->property, lcfirst(rtrim($this->getViolations()[0]->message, '.')));
    }
}
