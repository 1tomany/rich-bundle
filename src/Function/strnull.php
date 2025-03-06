<?php

namespace OneToMany\RichBundle\Function
{

    function strnull(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return !empty($value) ? $value : null;
    }

}
