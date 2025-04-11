<?php

namespace OneToMany\RichBundle\Tests\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use OneToMany\RichBundle\Test\Constraint\ResponseViolationMessage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

#[Group('UnitTests')]
#[Group('TestTests')]
#[Group('ConstraintTests')]
final class ResponseViolationMessageTest extends TestCase
{
    public function testToString(): void
    {
        $this->assertEquals('the property "name" has a violation message "Required"', new ResponseViolationMessage('name', 'Required')->toString());
    }

    public function testEvaluationRequiresResponseObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        new ResponseViolationMessage('name', 'Required')->evaluate('Vic Cherubini');
    }

    public function testEvaluationRequiresNonEmptyResponseContent(): void
    {
        $this->expectException(ExpectationFailedException::class);

        new ResponseViolationMessage('name', 'Required')->evaluate(new Response(''));
    }

    public function testEvaluationRequiresResponseContentToBeValidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content is not a valid JSON document.');

        new ResponseViolationMessage('name', 'Required')->evaluate(
            new Response('{"id: "Vic" {, "age": 40}')
        );
    }

    public function testEvaluationRequiresResponseContentToHaveViolationsProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content does not have a "violations" property.');

        new ResponseViolationMessage('name', 'Required')->evaluate(
            new Response('{"id": 10, "name": "Vic Cherubini"}')
        );
    }

    public function testEvaluationRequiresViolationsPropertyToBeAnArrayOfObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "violations" property of the response content must be an array.');

        $responseContent = json_encode([
            'violations' => 'Required',
        ]);

        $this->assertIsString($responseContent);

        new ResponseViolationMessage('name', 'Required')->evaluate(
            new Response($responseContent)
        );
    }

    public function testEvaluationRequiresEachViolationToHavePropertyAndMessageKeys(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $property = 'name';
        $message = 'Required';

        $responseContent = json_encode([
            'violations' => [
                [
                    'name' => $property,
                    'error' => $message,
                ],
                [
                    'property' => $property,
                    'error' => $message,
                ],
                [
                    'name' => $property,
                    'message' => $message,
                ],
            ],
        ]);

        $this->assertIsString($responseContent);

        new ResponseViolationMessage($property, $message)->evaluate(
            new Response($responseContent)
        );
    }

    public function testEvaluationRequiresAtLeastOneViolationToMatchPropertyAndMessage(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $responseContent = json_encode([
            'violations' => [
                [
                    'property' => 'name',
                    'message' => 'Required',
                ],
                [
                    'property' => 'age',
                    'message' => 'Positive',
                ],
            ],
        ]);

        $this->assertIsString($responseContent);

        new ResponseViolationMessage('name', 'Error')->evaluate(
            new Response($responseContent)
        );
    }

    public function testEvaluationSucceedsWhenAtLeastOneViolationMatchesPropertyAndMessage(): void
    {
        $property = 'email';
        $message = 'Invalid';

        $responseContent = json_encode([
            'violations' => [
                [
                    'property' => $property,
                    'message' => 'Required',
                ],
                [
                    'property' => $property,
                    'message' => $message,
                ],
                [
                    'property' => 'age',
                    'message' => 'Positive',
                ],
            ],
        ]);

        $this->assertIsString($responseContent);

        new ResponseViolationMessage($property, $message)->evaluate(
            new Response($responseContent)
        );
    }
}
