<?php

namespace OneToMany\RichBundle\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\HttpFoundation\Response;

use function is_object;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

abstract class AbstractResponseConstraint extends Constraint
{
    protected function validateResponse(mixed $response): object
    {
        if (!$response instanceof Response) {
            throw new UnexpectedTypeException($response, Response::class);
        }

        if (!$response->getContent()) {
            throw new InvalidArgumentException('The response content is empty.');
        }

        $json = json_decode($response->getContent(), false);

        if (!is_object($json) || JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('The response content is not a valid JSON document.');
        }

        return $json;
    }

    protected function failureDescription(mixed $response): string
    {
        return $this->toString();
    }
}
