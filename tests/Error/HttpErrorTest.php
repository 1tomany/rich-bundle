<?php

namespace OneToMany\RichBundle\Tests\Error;

use OneToMany\RichBundle\Attribute\HasErrorType;
use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Enum\ErrorType;
use OneToMany\RichBundle\Error\HttpError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

        $violations = new ConstraintViolationList([
            new ConstraintViolation('Required', null, [], null, null, null),
        ]);

        $exception = new ValidationFailedException(null, $violations);
        $this->assertNotEquals($message, $exception->getMessage());

        $httpError = new HttpError($exception);
        $this->assertEquals($message, $httpError->getMessage());
    }

    public function testConstructorGeneralizesMessageWhenExceptionIsAccessDeniedException(): void
    {
        $message = 'Access is denied.';

        $exception = new AccessDeniedException();
        $this->assertNotEquals($message, $exception->getMessage());

        $httpError = new HttpError($exception);
        $this->assertEquals($message, $httpError->getMessage());
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

        $this->assertEquals($exception->getMessage(), new HttpError($exception)->getMessage());
    }

    public function testConstructorGeneralizesMessageWithAllOtherExceptions(): void
    {
        $message = 'An unexpected error occurred.';
        $exception = new \Exception('Unrecoverable error');

        $this->assertNotEquals($message, $exception->getMessage());
        $this->assertEquals($message, new HttpError($exception)->getMessage());
    }

    public function testConstructorResolvesHeadersWhenExceptionImplementsHttpExceptionInterface(): void
    {
        $exception = new NotFoundHttpException('Not Found', null, 404, [
            'X-Token' => 'token',
            'X-Title' => 'Title',
        ]);

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
        $this->assertSame($exception->getHeaders(), new HttpError($exception)->getHeaders());
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

        $this->assertSame($errors, new HttpError(new ValidationFailedException(null, new ConstraintViolationList($violations)))->getViolations());
    }

    public function testConstructorFlattensStack(): void
    {
        $exception1 = new \Exception('Exception 1', 0, null);
        $exception2 = new \Exception('Exception 2', 0, $exception1);
        $exception3 = new \Exception('Exception 3', 0, $exception2);

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
        $exception = new \Exception('Error');
        $httpError = new HttpError($exception);

        $this->assertSame(ErrorType::create($exception), $httpError->getType());
    }

    public function testToString(): void
    {
        $httpError = new HttpError(new \Exception('File Not Found', 404));

        $this->assertSame("[{$httpError->getDescription()}] {$httpError->getMessage()}", (string) $httpError);
    }

    public function testGettingThrowable(): void
    {
        $exception = new \Exception('Error');

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

        $this->assertEquals("{$status} {$title}", new HttpError(new HttpException($status))->getDescription());
    }

    public function testGettingTitleFromInvalidHttpStatus(): void
    {
        /** @var non-empty-string $title */
        $title = Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];

        /** @var int $lastStatus */
        $lastStatus = array_key_last(Response::$statusTexts);

        $status = random_int($lastStatus + 1, $lastStatus * 2);
        $this->assertArrayNotHasKey($status, Response::$statusTexts);

        $exception = new \Exception($title, $status);
        $httpError = new HttpError($exception);

        $this->assertEquals($status, $exception->getCode());
        $this->assertEquals($title, $httpError->getTitle());
    }

    public function testGettingTitleFromValidHttpStatus(): void
    {
        /** @var int<100, 599> $status */
        $status = array_rand(Response::$statusTexts);
        $this->assertArrayHasKey($status, Response::$statusTexts);

        /** @var non-empty-string $title */
        $title = Response::$statusTexts[$status];

        $exception = new \Exception($title, $status);
        $httpError = new HttpError($exception);

        $this->assertEquals($status, $exception->getCode());
        $this->assertEquals($title, $httpError->getTitle());
    }

    #[DataProvider('providerStatusAndLogLevel')]
    public function testGettingLogLevel(int $status, string $logLevel): void
    {
        $this->assertSame($logLevel, new HttpError(new \Exception('Error', $status))->getLogLevel());
    }

    public function testGettingLogLevelWithAccessDeniedExceptionIsCritical(): void
    {
        $this->assertSame(LogLevel::CRITICAL, new HttpError(new AccessDeniedException())->getLogLevel());
    }

    /**
     * @return list<list<int|string>>
     */
    public static function providerStatusAndLogLevel(): array
    {
        $provider = [
            [0, LogLevel::CRITICAL],
            [100, LogLevel::INFO],
            [101, LogLevel::INFO],
            [102, LogLevel::INFO],
            [103, LogLevel::INFO],
            [200, LogLevel::INFO],
            [300, LogLevel::NOTICE],
            [301, LogLevel::NOTICE],
            [302, LogLevel::NOTICE],
            [302, LogLevel::NOTICE],
            [303, LogLevel::NOTICE],
            [304, LogLevel::NOTICE],
            [305, LogLevel::NOTICE],
            [307, LogLevel::NOTICE],
            [308, LogLevel::NOTICE],
            [400, LogLevel::ERROR],
            [401, LogLevel::ERROR],
            [402, LogLevel::ERROR],
            [403, LogLevel::CRITICAL],
            [404, LogLevel::ERROR],
            [405, LogLevel::ERROR],
            [406, LogLevel::ERROR],
            [407, LogLevel::ERROR],
            [408, LogLevel::ERROR],
            [409, LogLevel::ERROR],
            [410, LogLevel::ERROR],
            [411, LogLevel::ERROR],
            [412, LogLevel::ERROR],
            [413, LogLevel::ERROR],
            [414, LogLevel::ERROR],
            [415, LogLevel::ERROR],
            [416, LogLevel::ERROR],
            [417, LogLevel::ERROR],
            [418, LogLevel::ERROR],
            [421, LogLevel::ERROR],
            [422, LogLevel::ERROR],
            [423, LogLevel::ERROR],
            [424, LogLevel::ERROR],
            [425, LogLevel::ERROR],
            [426, LogLevel::ERROR],
            [428, LogLevel::ERROR],
            [429, LogLevel::ERROR],
            [431, LogLevel::ERROR],
            [451, LogLevel::ERROR],
            [500, LogLevel::CRITICAL],
            [501, LogLevel::CRITICAL],
            [502, LogLevel::CRITICAL],
            [503, LogLevel::CRITICAL],
            [504, LogLevel::CRITICAL],
            [505, LogLevel::CRITICAL],
            [506, LogLevel::CRITICAL],
            [507, LogLevel::CRITICAL],
            [508, LogLevel::CRITICAL],
            [510, LogLevel::CRITICAL],
            [511, LogLevel::CRITICAL],
        ];

        return $provider;
    }

    public function testHasUserMessage(): void
    {
        $exception = new \Exception('Error');
        $this->assertFalse(new HttpError($exception)->hasUserMessage());

        $exception = new #[HasUserMessage] class('Error') extends \Exception {};
        $this->assertTrue(new HttpError($exception)->hasUserMessage());
    }

    public function testIsNotCriticalWhenLogLevelIsNotCritical(): void
    {
        $this->assertFalse(new HttpError(new \Exception('Not Found', 404))->isCritical());
    }
}
