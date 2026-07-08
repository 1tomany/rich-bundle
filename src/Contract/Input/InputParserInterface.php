<?php

namespace OneToMany\RichBundle\Contract\Input;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidationFailedException;

interface InputParserInterface
{
    /**
     * @template C of CommandInterface
     *
     * @param class-string<InputInterface<C>> $type
     * @param array<string, mixed> $defaultData
     *
     * @return InputInterface<C>
     *
     * @throws HttpException when content is provided and the Content-Type header is missing or malformed
     * @throws HttpException when decoding the content as specified by the Content-Type header fails
     * @throws HttpException when denormalizing the content to the underlying InputInterface object fails
     * @throws HttpException when a property of an InputInterface object is marked to be nullified but is not nullable
     * @throws RuntimeException when the SourceUser attribute is used but the Symfony Security Bundle is not installed
     * @throws ValidationFailedException when a property of the InputInterface object is uninitialized after mapping
     */
    public function parse(Request $request, string $type, array $defaultData = []): InputInterface;

    /**
     * @template C of CommandInterface
     *
     * @param InputInterface<C> $input
     * @param ?array<non-empty-string> $groups
     *
     * @throws ValidationFailedException when validating the input fails
     */
    public function validate(InputInterface $input, ?array $groups = null): void;
}
