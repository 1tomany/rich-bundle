<?php

namespace OneToMany\RichBundle\Attribute;

use Symfony\Component\HttpFoundation\Request;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class SourceLocale extends SourceRequest
{
    public function __construct()
    {
        parent::__construct(null, true, false, function (Request $request): string {
            return $request->getLocale();
        });
    }
}
