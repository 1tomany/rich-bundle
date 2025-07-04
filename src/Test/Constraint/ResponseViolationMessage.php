<?php

namespace OneToMany\RichBundle\Test\Constraint;

use OneToMany\RichBundle\Exception\Contract\Schema\WrappedExceptionSchema;
use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;

use function is_object;
use function sprintf;

final class ResponseViolationMessage extends ResponseMatchesJsonSchema
{
    public function __construct(
        private readonly string $property,
        private readonly string $message,
    ) {
        parent::__construct(new WrappedExceptionSchema());
    }

    public function toString(): string
    {
        return sprintf('the property "%s" has a violation message "%s"', $this->property, $this->message);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function matches(mixed $response): bool
    {
        $json = $this->validateSchema($response, true);

        $hasPropertyAndMessage = false;

        // @phpstan-ignore-next-line
        foreach ($json->violations as $v) {
            if (!is_object($v)) {
                continue;
            }

            // @phpstan-ignore-next-line
            if ($this->property === $v->property) {
                // @phpstan-ignore-next-line
                if ($this->message === $v->message) {
                    $hasPropertyAndMessage = true;
                }
            }
        }

        return $hasPropertyAndMessage;
    }
}
