<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function is_a;

readonly class InputValueResolver implements ValueResolverInterface
{
    public function __construct(
        private InputParserInterface $inputParser,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @return list<InputInterface<CommandInterface>>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($type = $this->getResolvableType($argument->getType())) {
            $input = $this->inputParser->parse($request, $type);

            // Validate the InputInterface object
            $violations = $this->validator->validate($input);

            if ($violations->count() > 0) {
                throw new ValidationFailedException($input, $violations);
            }

            return [$input];
        }

        return [];
    }

    /**
     * @return ?class-string<InputInterface<CommandInterface>>
     */
    private function getResolvableType(?string $type): ?string
    {
        if (null === $type) {
            return null;
        }

        return is_a($type, InputInterface::class, true) ? $type : null;
    }
}
