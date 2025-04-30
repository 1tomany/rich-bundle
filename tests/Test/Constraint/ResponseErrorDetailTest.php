<?php

namespace OneToMany\RichBundle\Tests\Test\Constraint;

use OneToMany\RichBundle\Test\Constraint\Exception\InvalidArgumentException;
use OneToMany\RichBundle\Test\Constraint\Exception\UnexpectedTypeException;
use OneToMany\RichBundle\Test\Constraint\ResponseErrorDetail;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

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

        new ResponseErrorDetail('Error!')->evaluate(
            new Response('{"detail": "Error!"}')
        );
    }

    public function testEvaluationRequiresErrorDetailToMatchMessage(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $responseContent = json_encode([
            'status' => 404,
            'title' => 'Not Found',
            'detail' => 'User not found.',
            'violations' => [],
        ]);

        $this->assertIsString($responseContent);

        new ResponseErrorDetail('Error!')->evaluate(
            new Response($responseContent)
        );
    }

    public function testEvaluationSucceedsWhenErrorDetailMatchesMessage(): void
    {
        $detail = 'User not found.';

        $responseContent = json_encode([
            'status' => 404,
            'title' => 'Not Found',
            'detail' => $detail,
            'violations' => [],
        ]);

        $this->assertIsString($responseContent);

        new ResponseErrorDetail($detail)->evaluate(
            new Response($responseContent)
        );
    }
}
