<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

#[HasUserMessage]
final class MalformedContentException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The request could not be processed because the payload is malformed.', 400, $previous);
    }

}
