<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\SourceContainer;
use OneToMany\RichBundle\Attribute\SourceFile;
use OneToMany\RichBundle\Attribute\SourceIpAddress;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Attribute\SourceRoute;
use OneToMany\RichBundle\Attribute\SourceSecurity;
use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use OneToMany\RichBundle\ValueResolver\Exception\ContentTypeHeaderMissingException;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedRequestContentException;
use OneToMany\RichBundle\ValueResolver\Exception\PropertySourceNotMappedException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
// use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class InputValueResolver implements ValueResolverInterface
{
    private Request $request;
    private ParameterBag $body;
    private ParameterBag $data;

    public function __construct(
        private readonly ContainerBagInterface $containerBag,
        // private readonly DecoderInterface $decoder,
        private readonly SerializerInterface&DecoderInterface&DenormalizerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
        $this->body = new ParameterBag();
        $this->data = new ParameterBag();
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

        // Parse the HTTP request body
        $this->initializeDataSources(...[
            'request' => $request,
        ]);

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
                // The key name is the same as the property,
                // but it can be overwritten by the attribute
                $key = $propertySource->name ?? $property->name;

                if ($propertySource instanceof SourceContainer) {
                    $this->extractFromContainerBag($property->name, $key);
                }

                if ($propertySource instanceof SourceFile) {
                    $this->extractFromFiles($property->name, $key);
                }

                if ($propertySource instanceof SourceIpAddress) {
                    $this->extractClientIpAddress($property->name);
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

            /**
             * Finally, we attempt to get the default value for non-promoted
             * properties if the property was not mapped from the request.
             *
             * We're only interested in non-promoted properties because a
             * promoted property is also a constructor parameter, and the
             * Symfony Normalizer will the default value of the parameter.
             */
            if (!$this->data->has($property->name) && !$property->isPromoted()) {
                $this->attemptToAppendPropertyDefaultValueToData($property);
            }
        }

        try {
            /** @var InputInterface<CommandInterface> $input */
            $input = $this->serializer->denormalize($this->data->all(), $type, null, [
                'disable_type_enforcement' => true,
                'collect_denormalization_errors' => true,
            ]);
        } catch (SerializerExceptionInterface $e) {
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

    private function initializeDataSources(Request $request): void
    {
        $this->data->replace([]);
        $this->request = $request;

        if ($this->body->count()) {
            return;
        }

        // Resolve the format from the Content-Type
        $contentType = $request->headers->get(...[
            'key' => 'CONTENT_TYPE',
        ]);

        $format = $request->getFormat(...[
            'mimeType' => $contentType,
        ]);

        // Content-Type: multipart/form-data
        if (\in_array($format, ['form'])) {
            $this->body = new ParameterBag(
                $request->request->all()
            );

            return;
        }

        if (!$format && !empty($request->getContent())) {
            throw new ContentTypeHeaderMissingException();
        }

        if (!$format) {
            return;
        }

        try {
            // Attempt to decode all other formats
            $decodedContent = $this->serializer->decode(
                $request->getContent(), $format
            );

            if (!\is_array($decodedContent)) {
                throw new MalformedRequestContentException($format);
            }
        } catch (SerializerExceptionInterface $e) {
            throw new MalformedRequestContentException($format, $e);
        }

        $this->body = new ParameterBag(...[
            'parameters' => $decodedContent,
        ]);
    }

    private function extractFromContainerBag(string $property, string $key): void
    {
        if ($this->containerBag->has($key) && !$this->data->has($property)) {
            $this->appendToData($property, $this->containerBag->get($key));
        }
    }

    private function extractFromFiles(string $property, string $key): void
    {
        if ($this->request->files->has($key) && !$this->data->has($property)) {
            $this->appendToData($property, $this->request->files->get($key));
        }
    }

    private function extractClientIpAddress(string $property): void
    {
        $this->appendToData($property, $this->request->getClientIp());
    }

    private function extractFromQuery(string $property, string $key): void
    {
        if ($this->request->query->has($key) && !$this->data->has($property)) {
            $this->appendToData($property, $this->request->query->get($key));
        }
    }

    private function extractFromRequest(string $property, string $key): void
    {
        if ($this->body->has($key) && !$this->data->has($property)) {
            $this->appendToData($property, $this->body->get($key));
        }
    }

    private function extractFromRoute(string $property, string $key): void
    {
        if ($this->request->attributes->has($key) && !$this->data->has($property)) {
            $this->appendToData($property, $this->request->attributes->get($key));
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
        if (\is_string($value)) {
            $value = \trim($value);
        }

        $this->data->set($key, $value);
    }

    private function attemptToAppendPropertyDefaultValueToData(\ReflectionProperty $property): void
    {
        if (!$property->hasDefaultValue()) {
            throw new PropertySourceNotMappedException($property->name);
        }

        $this->appendToData($property->name, $property->getDefaultValue());
    }
}
