<?php

namespace OneToMany\RichBundle\Contract\Input;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use Symfony\Component\HttpFoundation\Request;

interface InputParserInterface
{
    /**
     * @param class-string<InputInterface<CommandInterface>> $type
     * @param array<string, mixed> $defaultData
     *
     * @return InputInterface<CommandInterface>
     */
    public function parse(Request $request, string $type, array $defaultData = []): InputInterface;
}
