<?php

namespace OneToMany\RichBundle\Contract;

interface InputInterface
{

    public function toCommand(): CommandInterface;

}
