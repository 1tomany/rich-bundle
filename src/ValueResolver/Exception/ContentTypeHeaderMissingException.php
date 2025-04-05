<?php

namespace OneToMany\RichBundle\ValueResolver\Exception;

use OneToMany\RichBundle\Exception\Attribute\HasUserMessage;

#[HasUserMessage]
final class ContentTypeHeaderMissingException extends \RuntimeException implements ExceptionInterface
{
    public function __construct()
    {
        parent::__construct('The request content could not be parsed because the Content-Type header was missing or malformed.', 422);
    }
}
