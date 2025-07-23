<?php

namespace OneToMany\RichBundle\Contract\Enum;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function in_array;

enum ErrorType: string
{
    case Data = 'data';
    case Domain = 'domain';
    case Logic = 'logic';
    case System = 'system';

    public static function create(
        \Throwable $throwable,
        int $httpStatus = 0,
        self $default = self::System,
    ): self {
        if ($throwable instanceof \Error) {
            return self::System;
        }

        // InvalidArgumentException extends LogicException
        $isDataException = in_array($throwable::class, [
            \InvalidArgumentException::class,
            ValidationFailedException::class,
        ]);

        if ($isDataException) {
            return self::Data;
        }

        // DomainException extends LogicException
        if ($throwable instanceof \DomainException) {
            return self::Domain;
        }

        if ($throwable instanceof \LogicException) {
            return self::Logic;
        }

        $isClientError = in_array($httpStatus, [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ]);

        if ($isClientError) {
            return self::Data;
        }

        if ($httpStatus >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            return self::System;
        }

        return $default;
    }
}
