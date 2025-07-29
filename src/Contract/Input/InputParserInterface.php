<?php

namespace OneToMany\RichBundle\Contract\Input;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use Symfony\Component\HttpFoundation\Request;

interface InputParserInterface
{
    /**
     * @template C of CommandInterface
     *
     * @param class-string<InputInterface<C>> $type
     * @param array<string, mixed> $defaultData
     *
     * @return InputInterface<C>
     */
    public function parse(Request $request, string $type, array $defaultData = []): InputInterface;
}
