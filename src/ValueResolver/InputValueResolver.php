<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\SourceContainer;
use OneToMany\RichBundle\Attribute\SourceContent;
use OneToMany\RichBundle\Attribute\SourceFile;
use OneToMany\RichBundle\Attribute\SourceHeader;
use OneToMany\RichBundle\Attribute\SourceIpAddress;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Attribute\SourceRoute;
use OneToMany\RichBundle\Attribute\SourceUser;
use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Validator\UninitializedProperties;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedContentTypeHeaderNotFoundException;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedDecodingContentFailedException;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedMappingRequestFailedException;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedPropertyNotNullableException;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedSecurityBundleMissingException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function call_user_func;
use function count;
use function in_array;
use function is_a;
use function is_array;
use function is_callable;
use function is_string;
use function trim;

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
        $this->initializeDataSources($request);

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
                    $this->extractContainer($property, $source, $name);
                }

                if ($source instanceof SourceContent) {
                    $this->extractContent($property, $source);
                }

                if ($source instanceof SourceFile) {
                    $this->extractFile($property, $source, $name);
                }

                if ($source instanceof SourceHeader) {
                    $this->extractHeader($property, $source, $name);
                }

                if ($source instanceof SourceIpAddress) {
                    $this->extractIpAddress($property, $source);
                }

                if ($source instanceof SourceQuery) {
                    $this->extractQuery($property, $source, $name);
                }

                if ($source instanceof SourceRequest) {
                    $this->extractRequest($property, $source, $name);
                }

                if ($source instanceof SourceRoute) {
                    $this->extractRoute($property, $source, $name);
                }

                if ($source instanceof SourceUser) {
                    $this->extractUser($property, $source);
                }
            }
        }

        try {
            /** @var InputInterface<CommandInterface> $input */
            $input = $this->serializer->denormalize($this->data->all(), $type, null, [
                'filter_bool' => true, 'disable_type_enforcement' => true,
            ]);
        } catch (\Throwable $e) {
            throw new ResolutionFailedMappingRequestFailedException();
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

        return is_a($type, InputInterface::class, true) ? $type : null;
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
        if (in_array($format, ['form'])) {
            $this->content = new ParameterBag($request->request->all());

            return;
        }

        $content = trim($request->getContent());

        if (!$format) {
            if (!empty($content)) {
                throw new ResolutionFailedContentTypeHeaderNotFoundException();
            }

            return;
        }

        if (!$content) {
            return;
        }

        try {
            $data = $this->serializer->decode($content, $format);
        } catch (SerializerExceptionInterface $e) {
        }

        if (!is_array($data ?? null) || (($e ?? null) instanceof \Throwable)) {
            throw new ResolutionFailedDecodingContentFailedException($format, $e ?? null);
        }

        $this->content = new ParameterBag($data);
    }

    private function isPropertyIgnored(\ReflectionProperty $property): bool
    {
        return 0 !== count($property->getAttributes(PropertyIgnored::class));
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

    private function extractContainer(\ReflectionProperty $property, SourceContainer $source, string $name): void
    {
        if ($this->containerBag->has($name) && !$this->data->has($property->name)) {
            $this->appendValue($property, $source, $this->containerBag->get($name));
        }
    }

    private function extractContent(\ReflectionProperty $property, SourceContent $source): void
    {
        $this->appendValue($property, $source, $this->request->getContent(false));
    }

    private function extractFile(\ReflectionProperty $property, SourceFile $source, string $name): void
    {
        if ($this->request->files->has($name) && !$this->data->has($property->name)) {
            $this->appendValue($property, $source, $this->request->files->get($name));
        }
    }

    private function extractHeader(\ReflectionProperty $property, SourceHeader $source, string $name): void
    {
        if ($this->request->headers->has($name) && !$this->data->has($property->name)) {
            $this->appendValue($property, $source, $this->request->headers->get($name));
        }
    }

    private function extractIpAddress(\ReflectionProperty $property, SourceIpAddress $source): void
    {
        $this->appendValue($property, $source, $this->request->getClientIp());
    }

    private function extractQuery(\ReflectionProperty $property, SourceQuery $source, string $name): void
    {
        if ($this->request->query->has($name) && !$this->data->has($property->name)) {
            $this->appendValue($property, $source, $this->request->query->get($name));
        }
    }

    private function extractRequest(\ReflectionProperty $property, SourceRequest $source, string $name): void
    {
        if ($this->content->has($name) && !$this->data->has($property->name)) {
            $this->appendValue($property, $source, $this->content->get($name));
        }
    }

    private function extractRoute(\ReflectionProperty $property, SourceRoute $source, string $name): void
    {
        if ($this->request->attributes->has($name) && !$this->data->has($property->name)) {
            $this->appendValue($property, $source, $this->request->attributes->get($name));
        }
    }

    private function extractUser(\ReflectionProperty $property, SourceUser $source): void
    {
        if (null === $this->tokenStorage) {
            throw new ResolutionFailedSecurityBundleMissingException($property->name);
        }

        $this->appendValue($property, $source, $this->tokenStorage->getToken()?->getUser());
    }

    private function appendValue(\ReflectionProperty $property, PropertySource $source, mixed $value): void
    {
        if (is_callable($callback = $source->callback)) {
            $value = call_user_func($callback, $value);
        }

        // Trim the value if the source indicates to and it is a string
        $value = $source->trim && is_string($value) ? trim($value) : $value;

        // Ensure nullified sources support null property values
        if ($source->nullify && !$property->getType()?->allowsNull()) {
            throw new ResolutionFailedPropertyNotNullableException($property->name);
        }

        // Nullify empty string values, leave other types alone
        $this->data->set($property->name, ($source->nullify && is_string($value) && empty($value)) ? null : $value);
    }
}
