<?php

namespace OneToMany\RichBundle\HTTP;

use Symfony\Component\HttpFoundation\Request;

trait RequestTrait // @phpstan-ignore trait.unused
{
    public const string REQUEST_ID_KEY = '_rich_request_id';

    private function generateRequestId(Request $request): void
    {
        $request->attributes->set(self::REQUEST_ID_KEY, \bin2hex(\random_bytes(6)));
    }
}
