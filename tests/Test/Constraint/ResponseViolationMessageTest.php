<?php

namespace OneToMany\RichBundle\Tests\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use OneToMany\RichBundle\Test\Constraint\ResponseViolationMessage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content is empty.');

        new ResponseViolationMessage('name', 'Required')->evaluate(new Response(''));
    }

    public function testEvaluationRequiresResponseContentToMatchJsonSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content does not match the JSON schema defined in "OneToMany\RichBundle\Exception\Contract\WrappedExceptionSchema".');

        new ResponseViolationMessage('name', 'Required')->evaluate(new JsonResponse(['id' => 10]));
    }

    public function testEvaluationRequiresAtLeastOneViolationToMatchPropertyAndMessage(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $response = new JsonResponse([
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

        new ResponseViolationMessage('name', 'Error')->evaluate($response);
    }

    public function testEvaluationSucceedsWhenAtLeastOneViolationMatchesPropertyAndMessage(): void
    {
        $this->expectNotToPerformAssertions();

        $response = new JsonResponse([
            'status' => 400,
            'title' => 'Bad Request',
            'detail' => 'Invalid data.',
            'violations' => [
                [
                    'property' => 'email',
                    'message' => 'Required',
                ],
                [
                    'property' => 'email',
                    'message' => 'Invalid',
                ],
                [
                    'property' => 'age',
                    'message' => 'Positive',
                ],
            ],
        ]);

        new ResponseViolationMessage('email', 'Invalid')->evaluate($response);
    }
}
