<?php

namespace OneToMany\RichBundle\Tests\Input\Fixtures\Input\Trait;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Tests\Input\Fixtures\Command\InputCommand;

trait ToCommandTrait
{
    /**
     * @see OneToMany\RichBundle\Contract\Action\InputInterface
     */
    public function toCommand(): CommandInterface
    {
        return new InputCommand();
    }
}
