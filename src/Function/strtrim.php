<?php

namespace OneToMany\RichBundle\Function
{

    function strtrim(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

}
