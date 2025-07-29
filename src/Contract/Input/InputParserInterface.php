<?php

namespace OneToMany\RichBundle\Contract\Input;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @template C of CommandInterface
 */
interface InputParserInterface
{
    /**
     * @param class-string<InputInterface<C>> $type
     * @param array<string, mixed> $defaultData
     *
     * @return InputInterface<C>
     */
    public function parse(Request $request, string $type, array $defaultData = []): InputInterface;
}
