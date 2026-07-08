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
     * @throws HttpException when the Content-Type header is invalid
     * @throws HttpException when decoding the request content fails
     * @throws HttpException when denormalizing the request content fails
     * @throws HttpException when a non-nullable property is configured to be nullified
     * @throws RuntimeException when the Symfony Security Bundle is not installed
     * @throws ValidationFailedException when a property is uninitialized after mapping
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

    /**
     * @template C of CommandInterface
     *
     * @param class-string<InputInterface<C>> $type
     * @param array<string, mixed> $defaultData
     * @param ?array<non-empty-string> $groups
     *
     * @return InputInterface<C>
     */
    public function parseAndValidate(Request $request, string $type, array $defaultData = [], ?array $groups = null): InputInterface;
}
