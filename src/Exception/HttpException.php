<?php

namespace OneToMany\RichBundle\Exception;

use OneToMany\RichBundle\Contract\Exception\ExceptionInterface;

class HttpException extends \Symfony\Component\HttpKernel\Exception\HttpException implements ExceptionInterface
{
}
