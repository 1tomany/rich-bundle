<?php

namespace OneToMany\RichBundle\Error;

use OneToMany\RichBundle\Contract\Error\HttpErrorInterface;
use OneToMany\RichBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @phpstan-import-type Stack from HttpErrorInterface
 * @phpstan-import-type Trace from HttpErrorInterface
 * @phpstan-import-type Violation from HttpErrorInterface
 */
class ConsoleError extends HttpError
{
    public function __construct(
        ValidationFailedException $throwable,
    ) {
        if (0 === $throwable->getViolations()->count()) {
            throw new InvalidArgumentException('The constraint violation list cannot be empty.');
        }

        return parent::__construct($throwable);
    }

    /**
     * @see \Stringable
     *
     * @return non-empty-string
     */
    public function __toString(): string
    {
    }
}
