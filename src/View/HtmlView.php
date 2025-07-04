<?php

namespace OneToMany\RichBundle\View;

use OneToMany\RichBundle\View\Exception\RuntimeException;

readonly class HtmlView extends View
{
    public function getFormat(): string
    {
        return 'html';
    }

    /**
     * @return non-empty-string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() ?: throw new RuntimeException('The template must be a non-empty string.');
    }
}
