<?php

namespace OneToMany\RichBundle\Tests\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use OneToMany\RichBundle\Test\Constraint\ResponseErrorDetail;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('UnitTests')]
#[Group('TestTests')]
#[Group('ConstraintTests')]
final class ResponseErrorDetailTest extends TestCase
{
    public function testToString(): void
    {
        $this->assertEquals('the "detail" property matches the message "Error!"', new ResponseErrorDetail('Error!')->toString());
    }

    public function testEvaluationRequiresResponseObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        new ResponseErrorDetail('Error!')->evaluate('Vic Cherubini');
    }

    public function testEvaluationRequiresNonEmptyResponseContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content is empty.');

        new ResponseErrorDetail('Error!')->evaluate(new Response(''));
    }

    public function testEvaluationRequiresResponseContentToMatchJsonSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response content does not match the JSON schema defined in "OneToMany\RichBundle\Exception\Contract\WrappedExceptionSchema".');

        new ResponseErrorDetail('Error!')->evaluate(new JsonResponse(['detail' => 'Error!']));
    }

    public function testEvaluationRequiresErrorDetailToMatchMessage(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $response = new JsonResponse([
            'status' => 404,
            'title' => 'Not Found',
            'detail' => 'User not found.',
            'violations' => [],
        ]);

        new ResponseErrorDetail('Error!')->evaluate($response);
    }

    public function testEvaluationSucceedsWhenErrorDetailMatchesMessage(): void
    {
        $this->expectNotToPerformAssertions();

        $response = new JsonResponse([
            'status' => 404,
            'title' => 'Not Found',
            'detail' => 'User not found.',
            'violations' => [],
        ]);

        new ResponseErrorDetail('User not found.')->evaluate($response);
    }
}
