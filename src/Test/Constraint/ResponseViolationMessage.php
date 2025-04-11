<?php

namespace OneToMany\RichBundle\Test\Constraint;

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

final class ResponseViolationMessage extends Constraint
{
    public function __construct(
        private readonly string $property,
        private readonly string $message,
    ) {
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
        if (!$response instanceof Response) {
            throw new UnexpectedTypeException($response, Response::class);
        }

        $content = $response->getContent();

        if (empty($content)) {
            return false;
        }

        $json = json_decode($content);

        if (!is_object($json) || JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('The response content is not a valid JSON document.');
        }

        if (!isset($json->violations)) {
            throw new InvalidArgumentException('The response content does not have a "violations" property.');
        }

        if (!is_array($json->violations)) {
            throw new InvalidArgumentException('The "violations" property of the response content must be an array.');
        }

        $hasPropertyAndMessage = false;

        foreach ($json->violations as $v) {
            if (!is_object($v)) {
                continue;
            }

            if (isset($v->property, $v->message)) {
                if ($this->property === $v->property) {
                    if ($this->message === $v->message) {
                        $hasPropertyAndMessage = true;
                    }
                }
            }
        }

        return $hasPropertyAndMessage;
    }

    /**
     * @param Response $response
     */
    protected function failureDescription($response): string
    {
        return $this->toString();
    }
}
