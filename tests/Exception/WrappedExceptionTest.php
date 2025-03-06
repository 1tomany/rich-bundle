<?php

namespace OneToMany\RichBundle\Tests\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\WrappedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Group('UnitTests')]
#[Group('ExceptionTests')]
final class WrappedExceptionTest extends TestCase
{

    #[DataProvider('providerHttpExceptionAndStatusCode')]
    public function testConstructorResolvesStatusFromHttpException(HttpException $exception, int $statusCode): void
    {
        $wrapped = new WrappedException($exception);
        $this->assertEquals($statusCode, $wrapped->getStatus());
    }

    /**
     * @return list<list<int|HttpException>>
     */
    public static function providerHttpExceptionAndStatusCode(): array
    {
        $provider = [
            [new AccessDeniedHttpException(), 403],
            [new BadRequestHttpException(), 400],
            [new ConflictHttpException(), 409],
            [new GoneHttpException(), 410],
            [new LengthRequiredHttpException(), 411],
            [new LockedHttpException(), 423],
            [new MethodNotAllowedHttpException(['GET']), 405],
            [new NotAcceptableHttpException(), 406],
            [new NotFoundHttpException(), 404],
            [new PreconditionFailedHttpException(), 412],
            [new PreconditionRequiredHttpException(), 428],
            [new ServiceUnavailableHttpException(), 503],
            [new TooManyRequestsHttpException(), 429],
            [new UnauthorizedHttpException('Bearer'), 401],
            [new UnprocessableEntityHttpException(), 422],
            [new UnsupportedMediaTypeHttpException(), 415],
        ];

        return $provider;
    }

    public function testConstructorMaintainsMessageWithHttpException(): void
    {
        $message = 'A customer with ID 1 was not found.';

        $exception = new NotFoundHttpException($message);
        $this->assertInstanceOf(HttpException::class, $exception);

        $wrapped = new WrappedException($exception);
        $this->assertEquals($message, $wrapped->getMessage());
    }

    public function testConstructorMaintainsMessageWithHasUserMessageAttribute(): void
    {
        $exception = new #[HasUserMessage] class('An error occurred!') extends \RuntimeException {};
        $this->assertEquals($exception->getMessage(), (new WrappedException($exception))->getMessage());
    }

    public function testConstructorGeneralizesMessageWithValidationFailedException(): void
    {
        $message = 'The data provided is not valid.';

        $violations = new ConstraintViolationList([
            new ConstraintViolation('Required', null, [], null, null, null)
        ]);

        $exception = new ValidationFailedException(null, $violations);
        $this->assertNotEquals($message, $exception->getMessage());

        $wrapped = new WrappedException($exception);
        $this->assertEquals($message, $wrapped->getMessage());
    }

    public function testConstructorGeneralizesMessageWithAllOtherExceptions(): void
    {
        $message = 'An unexpected error occurred.';

        $exception = new \RuntimeException('Database failure!');
        $this->assertNotEquals($message, $exception->getMessage());

        $wrapped = new WrappedException($exception);
        $this->assertEquals($message, $wrapped->getMessage());
    }

    public function testConstructorResolvesHeadersFromHttpException(): void
    {
        $headers = [
            'X-Token' => 'abc123',
            'X-UserId' => '98133'
        ];

        $exception = new HttpException(...[
            'statusCode' => 500,
            'headers' => $headers
        ]);

        $wrapped = new WrappedException($exception);

        $this->assertSame($headers, $wrapped->getHeaders());
        $this->assertSame($headers, $exception->getHeaders());
    }

    public function testConstructorExpandsViolations(): void
    {
        $errors = [
            [
                'property' => 'username',
                'message' => 'Invalid email address.'
            ],
            [
                'property' => 'password',
                'message' => 'Password too short.'
            ],
            [
                'property' => 'age',
                'message' => 'Too young.'
            ]
        ];

        $violations = \array_map(function(array $e): ConstraintViolationInterface {
            return new ConstraintViolation($e['message'], null, [], null, $e['property'], null);
        }, $errors);

        $exception = new ValidationFailedException(
            null, new ConstraintViolationList($violations)
        );

        $wrapped = new WrappedException($exception);
        $this->assertSame($errors, $wrapped->getViolations());
    }

    public function testConstructorResolvesStack(): void
    {
        $exception1 = new \Exception(...[
            'message' => 'Exception 1'
        ]);

        $exception2 = new \Exception(...[
            'message' => 'Exception 2',
            'previous' => $exception1
        ]);

        $exception3 = new \Exception(...[
            'message' => 'Exception 3',
            'previous' => $exception2
        ]);

        $stack = [
            [
                'class' => $exception3::class,
                'message' => $exception3->getMessage(),
                'file' => $exception3->getFile(),
                'line' => $exception3->getLine()
            ],
            [
                'class' => $exception2::class,
                'message' => $exception2->getMessage(),
                'file' => $exception2->getFile(),
                'line' => $exception2->getLine()
            ],
            [
                'class' => $exception1::class,
                'message' => $exception1->getMessage(),
                'file' => $exception1->getFile(),
                'line' => $exception1->getLine()
            ]
        ];

        $wrapped = new WrappedException($exception3);
        $this->assertSame($stack, $wrapped->getStack());
    }

    public function testGettingTitleFromInvalidHttpStatus(): void
    {
        /** @var non-empty-string $title */
        $title = Response::$statusTexts[
            Response::HTTP_INTERNAL_SERVER_ERROR
        ];

        $httpStatus = \random_int(1000, 2000);
        $this->assertArrayNotHasKey($httpStatus, Response::$statusTexts);

        $exception = new \RuntimeException($title, $httpStatus);
        $this->assertEquals($httpStatus, $exception->getCode());

        $wrapped = new WrappedException($exception);
        $this->assertEquals($title, $wrapped->getTitle());
    }

    public function testGettingTitleFromValidHttpStatus(): void
    {
        /** @var positive-int $httpStatus */
        $httpStatus = \array_rand(Response::$statusTexts);
        $this->assertArrayHasKey($httpStatus, Response::$statusTexts);

        /** @var non-empty-string $title */
        $title = Response::$statusTexts[$httpStatus];

        $exception = new \RuntimeException($title, $httpStatus);
        $this->assertEquals($httpStatus, $exception->getCode());

        $wrapped = new WrappedException($exception);
        $this->assertEquals($title, $wrapped->getTitle());
    }

}
