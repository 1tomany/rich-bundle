<?php

namespace OneToMany\RichBundle\Test\Constraint;

use OneToMany\RichBundle\Serializer\Contract\ExceptionSchema;
use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\HttpFoundation\Response;

use function is_array;
use function is_object;
use function json_decode;
use function json_last_error;
use function sprintf;

use const JSON_ERROR_NONE;

final class ResponseViolationMessage extends ResponseMatchesSchema
{
    public function __construct(
        private readonly string $property,
        private readonly string $message,
    ) {
        parent::__construct(ExceptionSchema::schema());
    }

    public function toString(): string
    {
        return sprintf('the property "%s" has a violation message "%s"', $this->property, $this->message);
    }

    /**
     * @param Response $response
     */
    protected function matches(mixed $response): bool
    {
        $this->assertIsResponse($response);

        if (!$jsonObject = $this->validateAgainstSchema($response->getContent())) {
            throw new InvalidArgumentException(\sprintf('The response content does not match the JSON schema defined in "%s".', ExceptionSchema::class));
        }

        \assert(\is_array($jsonObject->violations ?? null));

        $hasPropertyAndMessage = false;

        foreach ($jsonObject->violations as $v) {
            if (!\is_object($v)) {
                continue;
            }

            \assert(\property_exists($v, 'property'));
            \assert(\property_exists($v, 'message'));
            // && isset($v->property, $v->message));
            // && isset($v->property, $v->message));

if ($this->message === $v->message) {
                        $hasPropertyAndMessage = true;
                    }
        }

        return $hasPropertyAndMessage;
    }
}
