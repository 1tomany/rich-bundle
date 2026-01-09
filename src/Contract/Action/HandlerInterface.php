<?php

namespace OneToMany\RichBundle\Contract\Action;

/**
 * @template C of CommandInterface
 * @template R of ResultInterface
 */
interface HandlerInterface
{
    public const string METHOD = 'handle';

    /**
     * @param C $command
     *
     * @return R
     */
    public function handle(CommandInterface $command): ResultInterface;
}
