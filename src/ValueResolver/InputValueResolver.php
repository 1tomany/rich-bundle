<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\SourceContainer;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Attribute\SourceRoute;
use OneToMany\RichBundle\Attribute\SourceSecurity;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedContentException;
use OneToMany\RichBundle\ValueResolver\Exception\PropertySourceNotMappedException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class InputValueResolver implements ValueResolverInterface
{
    private ParameterBag $data;
    private Request $request;
    private InputBag $payload;

    public function __construct(
        private readonly ContainerBagInterface $containerBag,
        private readonly DenormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
        $this->data = new ParameterBag();
        $this->payload = new InputBag();
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

        // Initialize data sources
        $this->initializeRequestData($request);

        // Read the properties from the class
        $refClass = new \ReflectionClass($type);

        foreach ($refClass->getProperties() as $property) {
            // Skip ignored properties
            $ignored = $property->getAttributes(
                PropertyIgnored::class
            );

            if (count($ignored)) {
                continue;
            }

            /**
             * @var ?list<PropertySource>
             */
            $propertySources = null;

            foreach ($property->getAttributes(PropertySource::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $propertySources[] = $attribute->newInstance();
            }

            // Use the HTTP body by default if no sources are found
            $propertySources = $propertySources ?? [new SourceRequest()];

            foreach ($propertySources as $propertySource) {
                // Resolve the key name from the source attribute
                $key = $propertySource->name ?? $property->name;

                if ($propertySource instanceof SourceContainer) {
                    $this->extractFromContainerBag($property->name, $key);
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

            // Ensure a property has a value if possible
            if (!$this->data->has($property->name)) {
                if (!$property->isPromoted() && !$property->hasDefaultValue()) {
                    throw new PropertySourceNotMappedException($property->name);
                }

                if (!$property->isPromoted() && $property->hasDefaultValue()) {
                    $this->data->set($property->name, $property->getDefaultValue());
                }
            }
        }

        try {
            /** @var InputInterface<CommandInterface> $input */
            $input = $this->normalizer->denormalize($this->data->all(), $type, null, [
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

        return \is_a($type, InputInterface::class, true) ? $type : null;
    }

    private function initializeRequestData(Request $request): void
    {
        $this->data->replace([]);
        $this->request = $request;

        try {
            // Extract HTTP request body
            $this->payload = $request->getPayload();
        } catch (JsonException $e) {
            throw new MalformedContentException($e);
        }
    }

    private function extractFromContainerBag(string $property, string $sourceKey): void
    {
        if ($this->containerBag->has($sourceKey) && !$this->data->has($property)) {
            $this->appendToData($property, $this->containerBag->get($sourceKey));
        }
    }

    private function extractFromQuery(string $property, string $sourceKey): void
    {
        if ($this->request->query->has($sourceKey) && !$this->data->has($property)) {
            $this->appendToData($property, $this->request->query->get($sourceKey));
        }
    }

    private function extractFromRequest(string $property, string $sourceKey): void
    {
        if ($this->payload->has($sourceKey) && !$this->data->has($property)) {
            $this->appendToData($property, $this->payload->get($sourceKey));
        }
    }

    private function extractFromRoute(string $property, string $sourceKey): void
    {
        if ($this->request->attributes->has($sourceKey) && !$this->data->has($property)) {
            $this->appendToData($property, $this->request->attributes->get($sourceKey));
        }
    }

    private function extractFromToken(string $property): void
    {
        $token = $this->tokenStorage?->getToken();

        if (null !== $userId = $token?->getUserIdentifier()) {
            $this->appendToData($property, $userId);
        }
    }

    private function appendToData(string $key, mixed $value): void
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        $this->data->set($key, $value);
    }
}
