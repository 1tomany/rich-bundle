<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\SourceContainer;
use OneToMany\RichBundle\Attribute\SourcePayload;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRoute;
use OneToMany\RichBundle\Attribute\SourceSecurity;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedContentException;
use OneToMany\RichBundle\ValueResolver\Exception\MissingSourceException;
use OneToMany\RichBundle\ValueResolver\Exception\MissingSourceSecurityException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Exception\UnexpectedValueException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class InputValueResolver implements ValueResolverInterface
{

    private ParameterBag $sourceData;
    private InputBag $sourceRoute;
    private InputBag $sourcePayload;

    private const string ROUTE_PARAMETERS_KEY = '_route_params';

    public function __construct(
        private readonly ContainerBagInterface $container,
        private readonly DenormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    )
    {
        $this->sourceData = new ParameterBag();
        $this->sourceRoute = new InputBag();
        $this->sourcePayload = new InputBag();
    }

    /**
     * @return list<InputInterface<CommandInterface>>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Ensure we can resolve this argument
        $type = $this->getResolvableType(...[
            'type' => $argument->getType(),
        ]);

        if (!$type) {
            return [];
        }

        // Reset source data bags
        $this->sourceData->replace([]);
        $this->sourceRoute->replace([]);
        $this->sourcePayload->replace([]);

        try {
            // Extract request body content
            $this->sourcePayload->replace(
                $request->getPayload()->all()
            );
        } catch (JsonException $e) {
            throw new MalformedContentException($e);
        }

        try {
            // Extract route parameters from request
            $routeParams = $request->attributes->all(
                self::ROUTE_PARAMETERS_KEY
            );

            $this->sourceRoute->replace($routeParams);
        } catch (UnexpectedValueException $e) { }

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
                // Stop searching once a value has been found
                if ($this->sourceData->has($property->name)) {
                    continue;
                }

                $sourceValue = $property->getDefaultValue();
                // if ($property->hasDefaultValue()) {
                //     $propertyValue = $property->getDefaultValue();
                // }
                // $_value = $property->getDefaultValue();

                // Resolve the key name from the source data
                $sourceKey = $source->name ?? $property->name;

                if ($source instanceof SourceContainer) {
                    if (!$this->container->has($sourceKey) && $source->required) {
                        throw new MissingSourceException($source, $property->name, $sourceKey);
                    }

                    if ($this->container->has($sourceKey)) {
                        $sourceValue = $this->container->get($sourceKey);
                    }

                    $this->sourceData->set($property->name, $sourceValue);
                }

                /*
                if ($source instanceof SourceQuery) {
                    if (!$request->query->has($sourceKey) && $source->required) {
                        throw new \Exception('query has no ' . $sourceKey);
                    }

                    $_value = $request->query->get($sourceKey) ?? $_value;
                }

                if ($source instanceof SourceRoute) {
                    if (!$sourceRoute->has($sourceKey) && $source->required) {
                        throw new \Exception('no route param ' . $sourceKey);
                    }

                    $_value = $sourceRoute->get($sourceKey) ?? $_value;
                }
                */

                if ($source instanceof SourcePayload) {
                    if (!$this->sourcePayload->has($sourceKey) && $source->required) {
                        throw new MissingSourceException($source, $property->name, $sourceKey);
                    }

                    if ($this->sourcePayload->has($sourceKey)) {
                        $sourceValue = $this->sourcePayload->get($sourceKey);
                    }

                    $this->sourceData->set($property->name, $sourceValue);
                }

                if ($source instanceof SourceSecurity) {
                    $token = $this->tokenStorage?->getToken();

                    if (null === $token && $source->required) {
                        throw new MissingSourceSecurityException($property->name);
                    }

                    if (null !== $token?->getUserIdentifier()) {
                        $sourceValue = $token->getUserIdentifier();
                    }

                    $this->sourceData->set($property->name, $sourceValue);
                }
            }

            // Finally, ensure each property is present
            // in the data to be denormalized, even if
            // it could not be found in any input source.
            // $inputData[$property->name] ??= null;
        }

        try {
            /** @var InputInterface<CommandInterface> $input */
            $input = $this->normalizer->denormalize($this->sourceData->all(), $type, null, [
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
     * @return ?class-string<InputInterface<CommandInterface>>
     */
    private function getResolvableType(?string $type): ?string
    {
        if (null === $type) {
            return null;
        }

        return (is_a($type, InputInterface::class, true) ? $type : null);
    }

}
