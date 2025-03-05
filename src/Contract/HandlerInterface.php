<?php

namespace OneToMany\RichBundle\Contract;

interface HandlerInterface
{

    public function handle(CommandInterface $command): ResultInterface;

}
