<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\RichInput;
use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\SourceContainer;
use OneToMany\RichBundle\Attribute\SourcePayload;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRoute;
use OneToMany\RichBundle\Attribute\SourceSecurity;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedContentException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class InputValueResolver implements ValueResolverInterface
{

    public function __construct(
        private readonly ContainerBagInterface $container,
        private readonly DenormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    )
    {
    }

    /**
     * @return list<object>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $inputData = [];

        $type = $this->getResolvableType(...[
            'type' => $argument->getType(),
        ]);

        if (null === $type) {
            return $inputData;
        }

        try {
            $payload = $request->getPayload()->all();
        } catch (JsonException $e) {
            throw new MalformedContentException($e);
        }

        /** @var array<string, bool|int|string> $routeParams */
        $routeParams = $request->attributes->get('_route_params', []);

        $class = new \ReflectionClass($type);

        foreach ($class->getProperties() as $property) {
            $sources = null;

            // Ensure that the property is
            // a member of the class itself.
            // $isSameClass = in_array($class->name, [
            //     $property->getDeclaringClass()->name,
            // ]);

            // if (!$isSameClass) {
            //     continue;
            // }

            // Don't extract a property if it is explicitly ignored.
            $ignored = $property->getAttributes(PropertyIgnored::class);

            if (count($ignored)) {
                continue;
            }

            // Compile a list of property sources. This assumes
            // the order of the attributes on the property itself
            // is the priority that the data should be extracted.
            $richPropertyAttributes = $property->getAttributes(
                PropertySource::class, \ReflectionAttribute::IS_INSTANCEOF
            );

            foreach ($richPropertyAttributes as $attribute) {
                $sources[] = $attribute->newInstance();
            }

            // Ensure that if a property was not explicitly
            // ignored that it has at least one source. By
            // default, we assume it comes from the payload.
            $sources = $sources ?? [new SourcePayload()];

            foreach ($sources as $source) {
                $name = $source->name ?? $property->name;

                if (array_key_exists($property->name, $inputData)) {
                    continue;
                }

                if ($source instanceof SourceContainer) {
                    // if (!$this->container->has($name)) {
                    //     throw new \Exception('container has no ' . $name);
                    // }

                    try {
                        $inputData[$property->name] = $this->container->get($name);
                    } catch (ContainerExceptionInterface $e) {
                        if ($source->required) {
                            throw new \Exception('container has no ' . $name);
                        }

                        $inputData[$property->name] = $property->getDefaultValue();
                    }
                }

                if ($source instanceof SourceQuery) {
                    if (!$this->container->has($name)) {
                        if ($source->required) {
                            throw new \Exception('query has no ' . $name);
                        }

                        $inputData[$property->name] = $property->getDefaultValue();
                    } else {
                        $inputData[$property->name] = $request->query->get($name);
                    }
                }

                if ($source instanceof SourceRoute) {
                    if (!array_key_exists($name, $routeParams)) {
                        if ($source->required) {
                            throw new \Exception('no route param ' . $name);
                        }

                        $inputData[$property->name] = $property->getDefaultValue();
                    } else {
                        $inputData[$property->name] = $routeParams[$name];
                    }
                }

                if ($source instanceof SourcePayload) {
                    if (!array_key_exists($name, $payload)) {
                        if ($source->required) {
                            throw new \Exception('payload has no ' . $name);
                        }

                        $inputData[$property->name] = $property->getDefaultValue();
                    } else {
                        $inputData[$property->name] = $payload[$name];
                    }
                }

                if ($source instanceof SourceSecurity) {
                    if (null === $token = $this->tokenStorage?->getToken()) {
                        if ($source->required) {
                            throw new \Exception('no active security token');
                        }

                        $inputData[$property->name] = $property->getDefaultValue();
                    } else {
                        $inputData[$property->name] = $token->getUserIdentifier();
                    }
                }
            }

            // Finally, ensure each property is present
            // in the data to be denormalized, even if
            // it could not be found in any input source.
            $inputData[$property->name] ??= null;
        }

        try {
            /** @var object $input */
            $input = $this->normalizer->denormalize($inputData, $type, null, [
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

        $attributes = new \ReflectionClass($type)->getAttributes(
            RichInput::class, \ReflectionAttribute::IS_INSTANCEOF
        );

        return (count($attributes) ? $type : null);
    }

}
