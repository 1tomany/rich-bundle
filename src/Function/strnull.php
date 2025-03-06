<?php

namespace OneToMany\RichBundle\Function
{

    function strnull(mixed $value): ?string
    {
        $str = null;

        if (is_string($value)) {
            $str = trim($value);
        }

        return !empty($str) ? $str : null;
    }

}
