<?php

namespace OneToMany\RichBundle\Tests\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use OneToMany\RichBundle\Test\Constraint\ResponseMatchesJsonSchema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function random_int;

#[Group('UnitTests')]
#[Group('TestTests')]
#[Group('ConstraintTests')]
final class ResponseMatchesJsonSchemaTest extends TestCase
{
    public function testConstructorRequiresValidJsonDocument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The schema is not a valid JSON document.');

        new ResponseMatchesJsonSchema('{"invalid: " schema}');
    }

    public function testToString(): void
    {
        $this->assertEquals('the response content matches the JSON schema', new ResponseMatchesJsonSchema(['id' => 1])->toString());
    }

    public function testEvaluationRequiresResponseObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        new ResponseMatchesJsonSchema(['id' => 1])->evaluate(['id' => 100]);
    }

    public function testEvaluationRequiresNonEmptyResponseContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content is empty.');

        new ResponseMatchesJsonSchema(['id' => 1])->evaluate(new Response(''));
    }

    public function testEvaluationRequiresResponseContentToBeValidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content is not a valid JSON document.');

        new ResponseMatchesJsonSchema(['id' => 1])->evaluate(
            new Response('{"id: "Vic" {, "age": 40}')
        );
    }

    public function testEvaluationRequiresResponseContentToMatchSchema(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $schema = [
            'title' => 'Test Schema',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
            ],
            'required' => [
                'id',
            ],
            'additionalProperties' => false,
        ];

        $response = new JsonResponse([
            'name' => 'Vic Cherubini',
        ]);

        new ResponseMatchesJsonSchema($schema)->evaluate($response);
    }

    public function testEvaluationSucceedsWhenResponseContentMatchesSchema(): void
    {
        $this->expectNotToPerformAssertions();

        $schema = [
            'title' => 'Test Schema',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
            ],
            'required' => [
                'id',
                'name',
                'age',
            ],
            'additionalProperties' => false,
        ];

        $response = new JsonResponse([
            'id' => random_int(1, 100),
            'name' => 'Vic Cherubini',
            'age' => random_int(1, 100),
        ]);

        new ResponseMatchesJsonSchema($schema)->evaluate($response);
    }
}
