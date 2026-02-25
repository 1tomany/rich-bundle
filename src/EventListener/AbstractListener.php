<?php

namespace OneToMany\RichBundle\EventListener;

use Symfony\Component\Serializer\SerializerInterface;

abstract readonly class AbstractListener
{
    /**
     * @return non-empty-list<non-empty-string>
     */
    abstract public function getAcceptFormats(): array;

    /**
     * @return non-empty-list<non-empty-string>
     */
    abstract public function getContentFormats(): array;

    abstract protected function getSerializer(): SerializerInterface;
}
