<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Contract\Exception\ExceptionInterface;
use OneToMany\RichBundle\Exception\RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function sprintf;

#[HasUserMessage]
final class MalformedRequestContentException extends HttpException implements ExceptionInterface
{
    public function __construct(string $format, ?\Throwable $previous = null)
    {
        parent::__construct(400, sprintf('The request format is expected to be "%s" but an error occurred when decoding it.', $format), $previous);
    }
}
