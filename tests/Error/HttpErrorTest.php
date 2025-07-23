<?php

namespace OneToMany\RichBundle\Tests\Error;

use OneToMany\RichBundle\Attribute\HasErrorType;
use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Enum\ErrorType;
use OneToMany\RichBundle\Error\HttpError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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

use function array_key_last;
use function array_map;
use function array_rand;
use function random_int;

#[Group('UnitTests')]
#[Group('ErrorTests')]
final class HttpErrorTest extends TestCase
{
    #[DataProvider('providerHttpException')]
    public function testConstructorResolvesStatusWhenExceptionImplementsHttpExceptionInterface(HttpExceptionInterface $exception): void
    {
        $this->assertEquals($exception->getStatusCode(), new HttpError($exception)->getStatus());
    }

    /**
     * @return list<list<int|HttpExceptionInterface>>
     */
    public static function providerHttpException(): array
    {
        $provider = [
            [new AccessDeniedHttpException()],
            [new BadRequestHttpException()],
            [new ConflictHttpException()],
            [new GoneHttpException()],
            [new LengthRequiredHttpException()],
            [new LockedHttpException()],
            [new MethodNotAllowedHttpException(['GET'])],
            [new NotAcceptableHttpException()],
            [new NotFoundHttpException()],
            [new PreconditionFailedHttpException()],
            [new PreconditionRequiredHttpException()],
            [new ServiceUnavailableHttpException()],
            [new TooManyRequestsHttpException()],
            [new UnauthorizedHttpException('Bearer')],
            [new UnprocessableEntityHttpException()],
            [new UnsupportedMediaTypeHttpException()],
        ];

        return $provider;
    }

    public function testConstructorGeneralizesMessageWhenExceptionIsValidationFailedException(): void
    {
        $message = 'The data provided is not valid.';

        $constraintViolation = new ConstraintViolation(
            'Required', null, [], null, null, null,
        );

        $violations = new ConstraintViolationList(...[
            'violations' => [$constraintViolation],
        ]);

        $exception = new ValidationFailedException(null, $violations);

        $this->assertNotEquals($message, $exception->getMessage());
        $this->assertEquals($message, new HttpError($exception)->getMessage());
    }

    public function testConstructorMaintainsMessageWhenExceptionImplementsHttpExceptionInterface(): void
    {
        $message = 'Customer ID "1" was not found.';
        $exception = new NotFoundHttpException($message);

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
        $this->assertEquals($message, new HttpError($exception)->getMessage());
    }

    public function testConstructorMaintainsMessageWhenWithHttpStatusAttributeIsPresent(): void
    {
        $exception = new #[WithHttpStatus(500)] class('Error') extends \Exception {};

        $this->assertEquals($exception->getMessage(), new HttpError($exception)->getMessage());
    }

    public function testConstructorMaintainsMessageWhenHasUserMessageAttributeIsPresent(): void
    {
        $exception = new #[HasUserMessage] class('Error') extends \Exception {};

        $this->assertEquals($exception->getMessage(), (new HttpError($exception))->getMessage());
    }

    public function testConstructorGeneralizesMessageWithAllOtherExceptions(): void
    {
        $message = 'An unexpected error occurred.';
        $exception = new \RuntimeException('Unrecoverable error');

        $this->assertNotEquals($message, $exception->getMessage());
        $this->assertEquals($message, new HttpError($exception)->getMessage());
    }

    public function testConstructorResolvesHeadersWhenExceptionImplementsHttpExceptionInterface(): void
    {
        $headers = [
            'X-Token' => 'token',
            'X-Title' => 'Title',
        ];

        $exception = new NotFoundHttpException(...[
            'headers' => $headers,
        ]);

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
        $this->assertSame($headers, new HttpError($exception)->getHeaders());
    }

    public function testConstructorResolvesHeadersWhenWithHttpStatusAttributeIsPresent(): void
    {
        $exception = new #[WithHttpStatus(500, ['X-Token' => 'token'])] class('Error') extends \Exception {};

        $this->assertSame(['X-Token' => 'token'], new HttpError($exception)->getHeaders());
    }

    public function testConstructorExpandsViolations(): void
    {
        $errors = [
            [
                'property' => 'username',
                'message' => 'Invalid email address.',
            ],
            [
                'property' => 'password',
                'message' => 'Password too short.',
            ],
            [
                'property' => 'age',
                'message' => 'Too young.',
            ],
        ];

        $violations = array_map(function (array $e): ConstraintViolationInterface {
            return new ConstraintViolation($e['message'], null, [], null, $e['property'], null);
        }, $errors);

        $exception = new ValidationFailedException(
            null, new ConstraintViolationList($violations)
        );

        $this->assertSame($errors, new HttpError($exception)->getViolations());
    }

    public function testConstructorFlattensStack(): void
    {
        $exception1 = new \Exception(...[
            'message' => 'Exception 1',
        ]);

        $exception2 = new \Exception(...[
            'message' => 'Exception 2',
            'previous' => $exception1,
        ]);

        $exception3 = new \Exception(...[
            'message' => 'Exception 3',
            'previous' => $exception2,
        ]);

        $stackTrace = [
            [
                'class' => $exception3::class,
                'message' => $exception3->getMessage(),
                'file' => $exception3->getFile(),
                'line' => $exception3->getLine(),
            ],
            [
                'class' => $exception2::class,
                'message' => $exception2->getMessage(),
                'file' => $exception2->getFile(),
                'line' => $exception2->getLine(),
            ],
            [
                'class' => $exception1::class,
                'message' => $exception1->getMessage(),
                'file' => $exception1->getFile(),
                'line' => $exception1->getLine(),
            ],
        ];

        $this->assertSame($stackTrace, new HttpError($exception3)->getStack());
    }

    public function testConstructorResolvesType(): void
    {
        $exception = new \RuntimeException('Error');
        $errorType = ErrorType::create($exception);

        $this->assertSame($errorType, new HttpError($exception)->getType());
    }

    public function testGettingThrowable(): void
    {
        $exception = new \RuntimeException('Error');

        $this->assertSame($exception, new HttpError($exception)->getThrowable());
    }

    public function testGettingTypeResolvesErrorTypeWhenHasErrorTypeAttributeIsPresent(): void
    {
        $exception = new #[HasErrorType(ErrorType::Data)] class('Error') extends \Exception {};

        $this->assertSame(ErrorType::Data, new HttpError($exception)->getType());
    }

    public function testGettingDescriptionFromValidHttpStatus(): void
    {
        /** @var int<100, 599> $status */
        $status = array_rand(Response::$statusTexts);
        $this->assertArrayHasKey($status, Response::$statusTexts);

        /** @var non-empty-string $title */
        $title = Response::$statusTexts[$status];

        // Arrange: Create Error Description
        $description = "{$status} {$title}";

        // Assert: Descriptions Match
        $this->assertEquals($description, new HttpError(new HttpException($status))->getDescription());
    }

    public function testGettingTitleFromInvalidHttpStatus(): void
    {
        /** @var non-empty-string $title */
        $title = Response::$statusTexts[
            Response::HTTP_INTERNAL_SERVER_ERROR
        ];

        $lastHttpStatus = array_key_last(
            Response::$statusTexts
        );

        $httpStatus = random_int($lastHttpStatus + 1, $lastHttpStatus * 2);
        $this->assertArrayNotHasKey($httpStatus, Response::$statusTexts);

        $exception = new \RuntimeException($title, $httpStatus);

        $this->assertEquals($httpStatus, $exception->getCode());
        $this->assertEquals($title, new HttpError($exception)->getTitle());
    }

    public function testGettingTitleFromValidHttpStatus(): void
    {
        /** @var int<100, 599> $status */
        $status = array_rand(Response::$statusTexts);
        $this->assertArrayHasKey($status, Response::$statusTexts);

        /** @var non-empty-string $title */
        $title = Response::$statusTexts[$status];

        $exception = new \RuntimeException($title, $status);

        $this->assertEquals($status, $exception->getCode());
        $this->assertEquals($title, new HttpError($exception)->getTitle());
    }
}
