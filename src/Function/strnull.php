<?php

namespace OneToMany\RichBundle\Function
{

    function strnull(mixed $value): ?string
    {
        return is_string($value) ? trim($value) : null;
    }

}
