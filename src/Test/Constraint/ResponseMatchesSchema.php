<?php

namespace OneToMany\RichBundle\Test\Constraint;

use JsonSchema\Validator;
use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\HttpFoundation\Response;

use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function json_last_error;

use const JSON_ERROR_NONE;

final class ResponseMatchesSchema extends Constraint
{
    private readonly object $schema;

    /**
     * @param string|array<string, mixed>|object $schema
     */
    public function __construct(string|array|object $schema)
    {
        if ($schema && is_array($schema)) {
            $schema = json_encode($schema);
        }

        if ($schema && is_string($schema)) {
            $schema = json_decode($schema);
        }

        if (!is_object($schema)) {
            throw new InvalidArgumentException('The schema is not a valid JSON document.');
        }

        $this->schema = $schema;
    }

    public function toString(): string
    {
        return 'the response content matches the JSON schema';
    }

    /**
     * @param Response $response
     */
    protected function matches(mixed $response): bool
    {
        if (!$response instanceof Response) {
            throw new UnexpectedTypeException($response, Response::class);
        }

        // Decode Response Content
        $content = $response->getContent();

        if (empty($content)) {
            return false;
        }

        $json = json_decode($content);

        if (!is_object($json) || JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('The response content is not a valid JSON document.');
        }

        // Validate Against JSON Schema
        $jsonValidator = new Validator();

        $jsonValidator->validate(
            $json, $this->schema
        );

        return $jsonValidator->isValid();
    }

    /**
     * @param Response $response
     */
    protected function failureDescription($response): string
    {
        return $this->toString();
    }
}
