<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

use function is_a;

readonly class InputValueResolver implements ValueResolverInterface
{
    public function __construct(
        private InputParserInterface $inputParser,
    ) {
    }

    /**
     * @see Symfony\Component\HttpKernel\Controller\ValueResolverInterface
     *
     * @return list<InputInterface<CommandInterface>>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Ensure the argument type can be resolved
        $type = $this->getType($argument->getType());

        if (!$type) {
            return [];
        }

        // Decode, denormalize, and validate the InputInterface argument
        $input = $this->inputParser->parse($request, $type, validate: true);

        return [$input];
    }

    /**
     * @return ?class-string<InputInterface<CommandInterface>>
     */
    private function getType(?string $type): ?string
    {
        if (null === $type) {
            return null;
        }

        return is_a($type, InputInterface::class, true) ? $type : null;
    }
}
