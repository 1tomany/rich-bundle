<?php

namespace OneToMany\RichBundle\Test\Constraint;

use OneToMany\RichBundle\Contract\JsonSchemaInterface;
use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use Opis\JsonSchema\Validator;

use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;

class ResponseMatchesJsonSchema extends AbstractResponseConstraint
{
    private readonly Validator $validator;
    private readonly object $jsonSchema;
    private(set) public ?string $schemaTitle = null;
    private(set) public ?string $schemaClass = null;

    /**
     * @param string|array<string, mixed>|object $jsonSchema
     */
    public function __construct(string|array|object $jsonSchema)
    {
        if ($jsonSchema instanceof JsonSchemaInterface) {
            $this->schemaClass = $jsonSchema::class;
            $jsonSchema = $jsonSchema->__toString();
        }

        if ($jsonSchema && is_array($jsonSchema)) {
            $jsonSchema = json_encode($jsonSchema);
        }

        if ($jsonSchema && is_string($jsonSchema)) {
            $jsonSchema = json_decode($jsonSchema);
        }

        if (!is_object($jsonSchema)) {
            throw new InvalidArgumentException('The schema is not a valid JSON document.');
        }

        if (is_string($jsonSchema->title ?? null)) {
            $this->schemaTitle = $jsonSchema->title;
        }

        $this->jsonSchema = $jsonSchema;

        $this->validator = new Validator(...[
            'stop_at_first_error' => true,
        ]);
    }

    public function toString(): string
    {
        return 'the response content matches the JSON schema';
    }

    protected function matches(mixed $response): bool
    {
        return null !== $this->validateSchema($response);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateSchema(mixed $response, bool $throwOnInvalid = false): ?object
    {
        $json = $this->validateResponse(...[
            'response' => $response,
        ]);

        $result = $this->validator->validate(
            $json, $this->jsonSchema, null, null
        );

        if ($result->isValid()) {
            return $json;
        }

        if ($throwOnInvalid) {
            if (!$this->schemaClass && !$this->schemaTitle) {
                throw new InvalidArgumentException('The response content does not match the JSON schema.');
            }

            if (null !== $this->schemaClass) {
                throw new InvalidArgumentException(sprintf('The response content does not match the JSON schema defined in "%s".', $this->schemaClass));
            }

            throw new InvalidArgumentException(sprintf('The response content does not match the "%s" JSON schema.', $this->schemaTitle));
        }

        return null;
    }
}
