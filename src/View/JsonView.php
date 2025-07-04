<?php

namespace OneToMany\RichBundle\View;

readonly class JsonView extends View
{
    public function getFormat(): string
    {
        return 'json';
    }

    public function getTemplate(): null
    {
        return null;
    }
}
