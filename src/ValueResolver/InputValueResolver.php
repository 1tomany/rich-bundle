<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\RichInput;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedContentException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class InputValueResolver implements ValueResolverInterface
{

    public function __construct(
        private DenormalizerInterface $normalizer,
        private ValidatorInterface $validator,
    )
    {
    }

    /**
     * @return list<mixed>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $this->getResolvableType($argument->getType());

        if (null === $type) {
            return [];
        }

        // Inject: Query String Data
        $data = new InputBag($request->query->all());

        try {
            // Inject: Request Content Data
            $data->add($request->getPayload()->all());
        } catch (JsonException $e) {
            throw new MalformedContentException($e);
        }

        // Inject: Authenticated User Session Data
        // $data->add($this->securityToken->toArray());

        // Inject: Route Parameters
        if (true === $request->attributes->has('_route_params')) {
            $data->add($request->attributes->all('_route_params'));
        }

        try {
            $input = $this->normalizer->denormalize($data->all(), $type, null, [
                'disable_type_enforcement' => true,
                'collect_denormalization_errors' => true,
            ]);
        } catch (SerializerException $e) {
            throw new InvalidMappingException($e);
        }

        $violations = $this->validator->validate($input);

        if ($violations->count() > 0) {
            throw new ValidationFailedException($input, $violations);
        }

        return [$input];
    }

    private function getResolvableType(?string $type): ?string
    {
        if (null === $type) {
            return null;
        }

        try {
            $class = new \ReflectionClass($type);
        } catch (\ReflectionException $e) {
            return null;
        }

        $attrs = $class->getAttributes(RichInput::class);

        if (count($attrs) > 0) {
            return $type;
        }

        return null;
    }

}
