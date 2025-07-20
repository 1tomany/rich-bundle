<?php

namespace OneToMany\RichBundle\Contract\Action;

/**
 * @template C of CommandInterface
 */
interface InputInterface
{
    /**
     * @return C
     */
    public function toCommand(): CommandInterface;
}
