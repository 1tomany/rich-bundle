<?php

namespace OneToMany\RichBundle\Tests\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use OneToMany\RichBundle\Test\Constraint\ResponseMatchesSchema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

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

    public function testMatchingRequiresNonEmptyResponseContent(): void
    {
        $this->expectException(ExpectationFailedException::class);

        new ResponseMatchesSchema(['id' => 1])->evaluate(new Response(''));
    }

    public function testMatchingSchema(): void
    {
        $faker = \Faker\Factory::create();

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
        ];

        $content = \json_encode([
            'id' => \random_int(1, 100),
            'name' => $faker->name('male'),
            'age' => \random_int(1, 100),
        ]);

        $this->assertIsString($content);

        $matches = new ResponseMatchesSchema(['id' => 1])->evaluate(new Response($content), '', true);

        $this->assertTrue($matches);
    }
}
