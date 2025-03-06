<?php

namespace OneToMany\RichBundle\Function
{

    function strlower(mixed $value): string
    {
        return is_string($value) ? strtolower(trim($value)) : '';
    }

}
