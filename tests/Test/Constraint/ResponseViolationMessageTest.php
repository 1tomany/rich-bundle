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

    public function testEvaluationRequiresResponseContentToMatchJsonSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content does not match the JSON schema defined in "OneToMany\RichBundle\Serializer\Contract\ExceptionSchema".');

        new ResponseViolationMessage('name', 'Required')->evaluate(
            new Response('{"id": 10, "name": "Vic Cherubini"}')
        );
    }

    public function testEvaluationRequiresAtLeastOneViolationToMatchPropertyAndMessage(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $responseContent = json_encode([
            'status' => 400,
            'title' => 'Bad Request',
            'detail' => 'The data provided is not valid.',
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
            'status' => 400,
            'title' => 'Bad Request',
            'detail' => 'The data provided is not valid.',
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
