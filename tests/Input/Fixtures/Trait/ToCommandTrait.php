<?php

namespace OneToMany\RichBundle\Tests\Input\Fixtures\Trait;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Exception\RuntimeException;

trait ToCommandTrait
{
    /**
     * @see OneToMany\RichBundle\Contract\Action\InputInterface
     */
    public function toCommand(): CommandInterface
    {
        throw new RuntimeException('Not implemented!');
    }
}
