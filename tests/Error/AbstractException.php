<?php

namespace OneToMany\RichBundle\Tests\Error;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(401)]
abstract class AbstractException extends \RuntimeException
{
}
