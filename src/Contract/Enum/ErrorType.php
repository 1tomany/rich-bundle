<?php

namespace OneToMany\RichBundle\Contract\Enum;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function in_array;

enum ErrorType: string
{
    case Data = 'data';
    case Logic = 'logic';
    case System = 'system';

    public static function create(
        \Throwable $throwable,
        int $httpStatus = 0,
        self $default = self::System,
    ): self {
        $isDataType = in_array($throwable::class, [
            \InvalidArgumentException::class,
            ValidationFailedException::class,
        ]);

        if ($isDataType) {
            return self::Data;
        }

        $isLogicType = in_array($throwable::class, [
            \LogicException::class,
        ]);

        if ($isLogicType) {
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

        if ($httpStatus >= 500) {
            return self::System;
        }

        return $default;
    }
}
