<?php

namespace OneToMany\RichBundle\Tests\Contract\Enum;

use OneToMany\RichBundle\Contract\Enum\ErrorType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function array_rand;

#[Group('UnitTests')]
#[Group('ContractTests')]
#[Group('EnumTests')]
final class ErrorTypeTest extends TestCase
{
    public function testCreatingTypeFromThrowableUsesDefault(): void
    {
        $default = ErrorType::cases()[
            array_rand(ErrorType::cases())
        ];

        $this->assertSame($default, ErrorType::create(new \Exception(), default: $default));
    }

    #[DataProvider('providerThrowableAndErrorType')]
    public function testCreatingTypeFromThrowable(\Throwable $throwable, ErrorType $type): void
    {
        $this->assertSame($type, ErrorType::create($throwable));
    }

    /**
     * @return list<list<\Throwable|ErrorType>>
     */
    public static function providerThrowableAndErrorType(): array
    {
        $provider = [
            [new \Error(), ErrorType::System],
            [new \InvalidArgumentException(), ErrorType::Data],
            [new ValidationFailedException(null, new ConstraintViolationList([])), ErrorType::Data],
            [new \DomainException(), ErrorType::Domain],
            [new \LogicException(), ErrorType::Logic],
        ];

        return $provider;
    }

    #[DataProvider('providerHttpStatusAndErrorType')]
    public function testCreatingTypeFromHttpStatus(int $httpStatus, ErrorType $type): void
    {
        $this->assertSame($type, ErrorType::create(new \Exception(), $httpStatus));
    }

    /**
     * @return list<list<int|ErrorType>>
     */
    public static function providerHttpStatusAndErrorType(): array
    {
        $provider = [
            [Response::HTTP_BAD_REQUEST, ErrorType::Data],
            [Response::HTTP_NOT_FOUND, ErrorType::Data],
            [Response::HTTP_UNPROCESSABLE_ENTITY, ErrorType::Data],
            [Response::HTTP_INTERNAL_SERVER_ERROR, ErrorType::System],
            [Response::HTTP_NOT_IMPLEMENTED, ErrorType::System],
            [Response::HTTP_BAD_GATEWAY, ErrorType::System],
            [Response::HTTP_SERVICE_UNAVAILABLE, ErrorType::System],
            [Response::HTTP_GATEWAY_TIMEOUT, ErrorType::System],
            [Response::HTTP_VERSION_NOT_SUPPORTED, ErrorType::System],
            [Response::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL, ErrorType::System],
            [Response::HTTP_INSUFFICIENT_STORAGE, ErrorType::System],
            [Response::HTTP_LOOP_DETECTED, ErrorType::System],
            [Response::HTTP_NOT_EXTENDED, ErrorType::System],
            [Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED, ErrorType::System],
        ];

        return $provider;
    }
}
