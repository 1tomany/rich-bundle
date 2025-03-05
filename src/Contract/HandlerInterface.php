<?php

namespace OneToMany\RichBundle\Contract;

/**
 * @template C of CommandInterface
 * @template R of ResultInterface
 */
interface HandlerInterface
{

    /**
     * @param C $command
     * @return R
     */
    public function handle(CommandInterface $command): ResultInterface;

}
