<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Contract\Exception\ExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;

class HttpException extends SymfonyHttpException implements ExceptionInterface
{
}
