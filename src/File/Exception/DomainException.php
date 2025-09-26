<?php

namespace App\File\Exception;

use App\File\Contract\Exception\ExceptionInterface;

class DomainException extends \DomainException implements ExceptionInterface
{
}
