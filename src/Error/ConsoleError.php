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
        ConstraintViolationListInterface $violations,
    ) {
        if (0 === $violations->count()) {
            throw new InvalidArgumentException('The constraint violation list cannot be empty.');
        }

        parent::__construct(new ValidationFailedException(null, $violations));
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
        $message = $this->getViolations()[0]->message;

        if ($property = $this->getViolations()[0]->property) {
            return sprintf('The property "%s" is not valid: %s.', $property, lcfirst(rtrim($message, '.')));
        }

        return $message;
    }
}
