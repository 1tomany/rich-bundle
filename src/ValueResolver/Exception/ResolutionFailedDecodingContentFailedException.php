<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Attribute\HasUserMessage;
use OneToMany\RichBundle\Exception\HttpException;

use function sprintf;

#[HasUserMessage]
final class ResolutionFailedDecodingContentFailedException extends HttpException
{
    public function __construct(string $format, ?\Throwable $previous = null)
    {
        parent::__construct(400, sprintf('Resolving the request failed because the content could not be decoded as "%s".', $format), $previous);
    }
}
