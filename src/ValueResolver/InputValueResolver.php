<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\RichInput;
use OneToMany\RichBundle\Attribute\RichProperty;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedContentException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use \ReflectionClass;

final readonly class InputValueResolver implements ValueResolverInterface
{

    public function __construct(
        // private TokenStorageInterface $tokenStorage,
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

        try {
            $requestContent = $request->getPayload()->all();
        } catch (JsonException $e) {
            throw new MalformedContentException($e);
        }

        /** @var array<string, scalar> $routeParameters */
        $routeParameters = $request->attributes->get('_route_params', []);

        $propertyData = [];
        $propertySources = [];

        $reflClass = new ReflectionClass($type);
        $className = $reflClass->name;

        foreach ($reflClass->getProperties() as $property) {
            $propertySources[$property->name] = PropertySource::RequestContent;
            $propertyData[$property->name] = null;

            if ($property->getDeclaringClass()->name === $className) {
                foreach ($this->getAttributes($property) as $richProperty) {
                    if ($richProperty instanceof RichProperty) {
                        $propertySources[$property->name] = $richProperty->source;
                    }
                }
            }

            if ($propertySources[$property->name] === PropertySource::QueryString) {
                $propertyData[$property->name] = $request->query->get($property->name);
            }

            if ($propertySources[$property->name] === PropertySource::RouteParameter) {
                $propertyData[$property->name] = ($routeParameters[$property->name] ?? null);
            }

            if ($propertySources[$property->name] === PropertySource::RequestContent) {
                $propertyData[$property->name] = ($requestContent[$property->name] ?? null);
            }
        }

        /*
        // Inject: Query String Data
        $data = new InputBag($request->query->all());

        try {
            // Inject: Request Content Data
            $data->add($request->getPayload()->all());
        } catch (JsonException $e) {
            throw new MalformedContentException($e);
        }

        // Inject: Authenticated User ID
        // if (null !== $token = $this->tokenStorage->getToken()) {
        //     $data->set('userId', $token->getUserIdentifier());
        // }

        // Inject: Route Parameters
        if (true === $request->attributes->has('_route_params')) {
            $data->add($request->attributes->all('_route_params'));
        }
        */

        try {
            $input = $this->normalizer->denormalize($propertyData, $type, null, [
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

    /**
     * @return ?class-string
     */
    private function getResolvableType(?string $type): ?string
    {
        if (null === $type) {
            return null;
        }

        if (!class_exists($type)) {
            return null;
        }

        $attrs = new ReflectionClass($type)
            ->getAttributes(RichInput::class);

        return (1 === count($attrs) ? $type : null);
    }

    private function getAttributes(\ReflectionMethod|\ReflectionClass|\ReflectionProperty $reflection): iterable
    {
        // foreach ($reflection->getAttributes(GroupSequence::class) as $attribute) {
        //     yield $attribute->newInstance();
        // }
        // foreach ($reflection->getAttributes(GroupSequenceProvider::class) as $attribute) {
        //     yield $attribute->newInstance();
        // }
        foreach ($reflection->getAttributes(RichProperty::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            yield $attribute->newInstance();
        }
    }

}
