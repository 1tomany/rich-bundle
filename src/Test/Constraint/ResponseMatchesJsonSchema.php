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
    private readonly ?string $class;

    /**
     * @param string|array<string, mixed>|object $schema
     */
    public function __construct(string|array|object $schema)
    {
        $this->class = is_object($schema) ? $schema::class : null;

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
            $json, $this->schema, null, null
        );

        if ($result->isValid()) {
            return $json;
        }

        if ($throwOnInvalid) {
            if (null !== $this->class) {
                throw new InvalidArgumentException(sprintf('The response content does not match the JSON schema defined in "%s".', $this->class));
            }

            if (is_string($this->schema->title ?? null)) {
                throw new InvalidArgumentException(sprintf('The response content does not match the "%s" JSON schema.', $this->schema->title));
            }

            throw new InvalidArgumentException('The response content does not match the JSON schema.');
        }

        return null;
    }
}
