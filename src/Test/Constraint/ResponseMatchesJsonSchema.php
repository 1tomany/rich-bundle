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
    private readonly object $schema;

    /**
     * @param string|array<string, mixed>|object $schema
     */
    public function __construct(string|array|object $schema)
    {
        if ($schema instanceof JsonSchemaInterface) {
            $schema = $schema->__toString();
        }

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
        $json = $this->validateResponse(...[
            'response' => $response,
        ]);

        return $this->validateSchema($json);
    }

    protected function validateSchema(object $json): bool
    {
        $result = $this->validator->validate(
            $json, $this->schema, null, null
        );

        return $result->isValid();
    }
}
