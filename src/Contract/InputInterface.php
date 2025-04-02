<?php

namespace OneToMany\RichBundle\Contract;

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
