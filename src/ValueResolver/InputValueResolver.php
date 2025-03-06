<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\SourceContainer;
use OneToMany\RichBundle\Attribute\SourcePayload;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRequest;
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

    // private ParameterBag $properties;
    private ParameterBag $sourceData;
    private InputBag $sourceQuery;
    private InputBag $sourceRoute;
    private InputBag $sourceRequest;

    private const string ROUTE_PARAMETERS_KEY = '_route_params';

    public function __construct(
        private readonly ContainerBagInterface $container,
        private readonly DenormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    )
    {
        // $this->properties = new ParameterBag();
        $this->sourceData = new ParameterBag();
        $this->sourceQuery = new InputBag();
        $this->sourceRoute = new InputBag();
        $this->sourceRequest = new InputBag();
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

        // duh
        $propertySources = []; //new ParameterBag();

        // Reset source data bags
        $this->sourceData->replace([]);
        $this->sourceRoute->replace([]);
        $this->sourceRequest->replace([]);

        // Extract HTTP query string
        $this->sourceQuery->replace(
            $request->query->all()
        );

        try {
            // Extract HTTP request body
            $this->sourceRequest->replace(
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
            // Ensure that the property is
            // a member of the class itself.
            // $isSameClass = in_array($class->name, [
            //     $property->getDeclaringClass()->name,
            // ]);

            // if (!$isSameClass) {
            //     continue;
            // }

            // Don't extract a property if it is explicitly ignored
            $ignored = $property->getAttributes(PropertyIgnored::class);

            if (count($ignored)) {
                continue;
            }

            $propertySources = [];

            foreach ($property->getAttributes(PropertySource::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                array_push($propertySources, $attribute->newInstance());
            }

            if (!count($propertySources)) {
                $propertySources = [
                    new SourceRequest(),
                ];
            }

            foreach ($propertySources as $propertySource) {
                // if ($this->sourceData->has($property->name)) {
                //     continue;
                // }

                // Resolve the key name from the source data
                $key = $propertySource->name ?? $property->name;

                if ($propertySource instanceof SourceContainer) {
                    $this->extractFromContainer($property->name, $key);
                }

                if ($propertySource instanceof SourceQuery) {
                    $this->extractFromQuery($property->name, $key);
                }

                if ($propertySource instanceof SourceRequest) {
                    $this->extractFromRequest($property->name, $key);
                }

                if ($propertySource instanceof SourceRoute) {
                    $this->extractFromRoute($property->name, $key);
                }

                if ($propertySource instanceof SourceSecurity) {
                    $this->extractFromToken($property->name);
                }
            }

            /*
            if (!$this->sourceData->has($property->name)) {
                if (!$property->isPromoted() && !$property->hasDefaultValue()) {
                    throw new \Exception('no default property value for ' . $property->name);
                }

                $this->sourceData->set($property->name, $property->getDefaultValue());
            }
            */
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

    private function extractFromContainer(string $property, string $sourceKey): void
    {
        if ($this->container->has($sourceKey) && !$this->sourceData->has($property)) {
            $this->sourceData[$property] = $this->container->get($sourceKey);
        }
    }

    private function extractFromQuery(string $property, string $sourceKey): void
    {
        if ($this->sourceQuery->has($sourceKey) && !$this->sourceData->has($property)) {
            $this->sourceData[$property] = $this->sourceQuery->get($sourceKey);
        }
    }

    private function extractFromRequest(string $property, string $sourceKey): void
    {
        if ($this->sourceRequest->has($sourceKey) && !$this->sourceData->has($property)) {
            $this->sourceData[$property] = $this->sourceRequest->get($sourceKey);
        }
    }

    private function extractFromRoute(string $property, string $sourceKey): void
    {
        if ($this->sourceRoute->has($sourceKey) && !$this->sourceData->has($property)) {
            $this->sourceData[$property] = $this->sourceRoute->get($sourceKey);
        }
    }

    private function extractFromToken(string $property): void
    {
        $token = $this->tokenStorage?->getToken();

        if (null !== $userId = $token?->getUserIdentifier()) {
            $this->sourceData->set($property, $userId);
        }
    }

}
