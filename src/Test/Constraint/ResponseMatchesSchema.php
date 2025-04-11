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

class ResponseMatchesSchema extends Constraint
{
    private readonly Validator $jsonSchemaValidator;
    private readonly object $jsonSchema;

    /**
     * @param string|array<string, mixed>|object $jsonSchema
     */
    public function __construct(string|array|object $jsonSchema)
    {
        if ($jsonSchema && is_array($jsonSchema)) {
            $jsonSchema = json_encode($jsonSchema);
        }

        if ($jsonSchema && is_string($jsonSchema)) {
            $jsonSchema = json_decode($jsonSchema);
        }

        if (!is_object($jsonSchema)) {
            throw new InvalidArgumentException('The schema is not a valid JSON document.');
        }

        $this->jsonSchema = $jsonSchema;
        $this->jsonSchemaValidator = new Validator();
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
        $this->assertIsResponse($response);

        return false !== $this->validateAgainstSchema($response->getContent());
    }

    /**
     * @param Response $response
     */
    protected function failureDescription($response): string
    {
        return $this->toString();
    }

    protected function assertIsResponse(mixed $response): bool
    {
        if (!$response instanceof Response) {
            throw new UnexpectedTypeException($response, Response::class);
        }

        return true;
    }

    protected function validateAgainstSchema(false|string $content): false|object
    {
        if (empty($content)) {
            return false;
        }

        $json = json_decode($content);

        if (!is_object($json) || JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('The response content is not a valid JSON document.');
        }

        $this->jsonSchemaValidator->validate(
            $json, $this->jsonSchema
        );

        $isValid = $this->jsonSchemaValidator->isValid();

        return (is_object($json) && $isValid) ? $json : false;
    }
}
