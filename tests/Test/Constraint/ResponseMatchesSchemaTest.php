<?php

namespace OneToMany\RichBundle\Tests\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use OneToMany\RichBundle\Test\Constraint\ResponseMatchesSchema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;
use function random_int;

#[Group('UnitTests')]
#[Group('TestTests')]
#[Group('ConstraintTests')]
final class ResponseMatchesSchemaTest extends TestCase
{
    public function testConstructorRequiresValidJsonDocument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The schema is not a valid JSON document.');

        new ResponseMatchesSchema('{"invalid: " schema}');
    }

    public function testToString(): void
    {
        $this->assertEquals('the response content matches the JSON schema', new ResponseMatchesSchema(['id' => 1])->toString());
    }

    public function testEvaluationRequiresResponseObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        new ResponseMatchesSchema(['id' => 1])->evaluate(['id' => 100]);
    }

    public function testEvaluationRequiresNonEmptyResponseContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content is empty.');

        new ResponseMatchesSchema(['id' => 1])->evaluate(new Response(''));
    }

    public function testEvaluationRequiresResponseContentToBeValidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content is not a valid JSON document.');

        new ResponseMatchesSchema(['id' => 1])->evaluate(
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

        $responseContent = json_encode([
            'name' => 'Vic Cherubini',
        ]);

        $this->assertIsString($responseContent);

        new ResponseMatchesSchema($schema)->evaluate(
            new Response($responseContent),
        );
    }

    public function testEvaluationSucceedsWhenResponseContentMatchesSchema(): void
    {
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

        $responseContent = json_encode([
            'id' => random_int(1, 100),
            'name' => 'Vic Cherubini',
            'age' => random_int(1, 100),
        ]);

        $this->assertIsString($responseContent);

        new ResponseMatchesSchema($schema)->evaluate(
            new Response($responseContent),
        );
    }
}
