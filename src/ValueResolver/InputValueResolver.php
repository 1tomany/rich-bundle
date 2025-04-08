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
use OneToMany\RichBundle\Validator\UninitializedProperties;
use OneToMany\RichBundle\ValueResolver\Exception\ContentTypeHeaderMissingException;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedRequestContentException;
use OneToMany\RichBundle\ValueResolver\Exception\PropertyIsNotNullableException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class InputValueResolver implements ValueResolverInterface
{
    private Request $request;
    private ParameterBag $content;
    private ParameterBag $data;

    public function __construct(
        private readonly ContainerBagInterface $containerBag,
        private readonly SerializerInterface&DecoderInterface&DenormalizerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
        $this->content = new ParameterBag();
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
        $class = new \ReflectionClass($type);

        foreach ($class->getProperties() as $property) {
            // Skip explicitly ignored properties
            if ($this->isPropertyIgnored($property)) {
                continue;
            }

            foreach ($this->findSources($property) as $source) {
                $name = $source->getName($property->name);

                if ($source instanceof SourceContainer) {
                    $this->extractFromContainerBag(
                        $property, $source, $name
                    );
                }

                if ($source instanceof SourceFile) {
                    $this->extractFromUploadedFiles(
                        $property, $source, $name
                    );
                }

                if ($source instanceof SourceIpAddress) {
                    $this->extractClientIpAddress(
                        $property, $source
                    );
                }

                if ($source instanceof SourceQuery) {
                    $this->extractFromQueryString(
                        $property, $source, $name
                    );
                }

                if ($source instanceof SourceRequest) {
                    $this->extractFromRequestContent(
                        $property, $source, $name
                    );
                }

                if ($source instanceof SourceRoute) {
                    $this->extractFromRouteParameters(
                        $property, $source, $name
                    );
                }

                if ($source instanceof SourceSecurity) {
                    $this->extractFromSecurityToken(
                        $property, $source
                    );
                }
            }
        }

        try {
            /** @var InputInterface<CommandInterface> $input */
            $input = $this->serializer->denormalize($this->data->all(), $type, null, [
                AbstractNormalizer::FILTER_BOOL => true,
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]);
        } catch (\Throwable $e) {
            throw new InvalidMappingException($e);
        }

        // Ensure all input class properties are mapped
        $violations = $this->validator->validate($input, [
            new UninitializedProperties(),
        ]);

        // Validate the input class itself
        if (0 === $violations->count()) {
            $violations = $this->validator->validate($input);
        }

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

        if ($this->content->count()) {
            return;
        }

        // Resolve the format from the Content-Type
        $format = $request->getContentTypeFormat();

        // Content-Type: multipart/form-data
        if (\in_array($format, ['form'])) {
            $this->content = new ParameterBag(
                $request->request->all()
            );

            return;
        }

        if (!$format) {
            if (!empty($request->getContent())) {
                throw new ContentTypeHeaderMissingException();
            }

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

        $this->content = new ParameterBag(...[
            'parameters' => $decodedContent,
        ]);
    }

    private function isPropertyIgnored(\ReflectionProperty $property): bool
    {
        return 0 !== \count($property->getAttributes(PropertyIgnored::class));
    }

    /**
     * @return list<PropertySource>
     */
    private function findSources(\ReflectionProperty $property): array
    {
        $propertySources = null;

        foreach ($property->getAttributes(PropertySource::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $propertySources[] = $attribute->newInstance();
        }

        return $propertySources ?? [new SourceRequest()];
    }

    private function extractFromContainerBag(\ReflectionProperty $property, PropertySource $source, string $name): void
    {
        if ($this->containerBag->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->containerBag->get($name));
        }
    }

    private function extractFromUploadedFiles(\ReflectionProperty $property, PropertySource $source, string $name): void
    {
        if ($this->request->files->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->request->files->get($name));
        }
    }

    private function extractClientIpAddress(\ReflectionProperty $property, PropertySource $source): void
    {
        $this->appendPropertyValue($property, $source, $this->request->getClientIp());
    }

    private function extractFromQueryString(\ReflectionProperty $property, PropertySource $source, string $name): void
    {
        if ($this->request->query->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->request->query->get($name));
        }
    }

    private function extractFromRequestContent(\ReflectionProperty $property, PropertySource $source, string $name): void
    {
        if ($this->content->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->content->get($name));
        }
    }

    private function extractFromRouteParameters(\ReflectionProperty $property, PropertySource $source, string $name): void
    {
        if ($this->request->attributes->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->request->attributes->get($name));
        }
    }

    private function extractFromSecurityToken(\ReflectionProperty $property, PropertySource $source): void
    {
        $token = $this->tokenStorage?->getToken();

        if (null !== $userId = $token?->getUserIdentifier()) {
            $this->appendPropertyValue($property, $source, $userId);
        }
    }

    private function appendPropertyValue(
        \ReflectionProperty $property,
        PropertySource $source,
        mixed $value,
    ): void {
        if (true === \is_scalar($value)) {
            $value = (string) $value;

            if (true === $source->trim) {
                $value = \trim($value);
            }

            if (true === $source->nullify && '' === $value) {
                if (true !== $property->getType()?->allowsNull()) {
                    throw new PropertyIsNotNullableException($property->name);
                }

                $value = null;
            }
        }

        $this->data->set($property->name, $value);
    }
}
