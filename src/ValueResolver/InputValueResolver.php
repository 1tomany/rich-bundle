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
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Exception\LogicException;
use OneToMany\RichBundle\Exception\RuntimeException;
use OneToMany\RichBundle\Validator\UninitializedProperties;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
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

use function count;
use function in_array;
use function is_a;
use function is_array;
use function is_scalar;
use function method_exists;
use function sprintf;
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
            throw new HttpException(400, 'The request could not be processed because the content is malformed and could not be mapped correctly.', $e);
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
                throw new HttpException(422, 'The request content could not be parsed because the Content-Type header was missing or malformed.');
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
            throw new HttpException(400, sprintf('The request format is expected to be "%s" but an error occurred when decoding it.', $format), $e ?? null);
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
            $this->appendPropertyValue($property, $source, $this->containerBag->get($name));
        }
    }

    private function extractContent(\ReflectionProperty $property, SourceContent $source): void
    {
        $this->appendPropertyValue($property, $source, $this->request->getContent(false));
    }

    private function extractQuery(\ReflectionProperty $property, SourceQuery $source, string $name): void
    {
        if ($this->request->query->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->request->query->get($name));
        }
    }

    private function extractFile(\ReflectionProperty $property, SourceFile $source, string $name): void
    {
        if ($this->request->files->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->request->files->get($name));
        }
    }

    private function extractHeader(\ReflectionProperty $property, SourceHeader $source, string $name): void
    {
        if ($this->request->headers->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->request->headers->get($name));
        }
    }

    private function extractRequest(\ReflectionProperty $property, SourceRequest $source, string $name): void
    {
        if ($this->content->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->content->get($name));
        }
    }

    private function extractRoute(\ReflectionProperty $property, SourceRoute $source, string $name): void
    {
        if ($this->request->attributes->has($name) && !$this->data->has($property->name)) {
            $this->appendPropertyValue($property, $source, $this->request->attributes->get($name));
        }
    }

    private function extractIpAddress(\ReflectionProperty $property, SourceIpAddress $source): void
    {
        $this->appendPropertyValue($property, $source, $this->request->getClientIp());
    }

    private function extractUser(\ReflectionProperty $property, SourceUser $source): void
    {
        if (null === $this->tokenStorage) {
            throw new LogicException(sprintf('The property "%s" could not be extracted because the Symfony Security Bundle is not installed. Try running "composer require symfony/security-bundle".', $property));
        }

        if ($user = $this->tokenStorage->getToken()?->getUser()) {
            if (!is_a($source->class, $user::class, true)) {
                throw new RuntimeException(sprintf('The property "%s" could not be extracted because the user extracted is not of type "%s".', $property, $source->class));
            }

            if (!method_exists($user, $source->getter)) {
                throw new RuntimeException(sprintf('The property "%s" could not be extracted because the getter method "%s::%s()" does not exist.', $property, $source->class, $source->getter));
            }

            $userValue = $user->{$source->getter}();
        } else {
            $userValue = null;
        }

        $this->appendPropertyValue($property, $source, $userValue);
    }

    private function appendPropertyValue(
        \ReflectionProperty $property,
        PropertySource $source,
        mixed $value,
    ): void {
        if (true === is_scalar($value)) {
            $value = (string) $value;

            if (true === $source->trim) {
                $value = trim($value);
            }

            if (true === $source->nullify && '' === $value) {
                if (true !== $property->getType()?->allowsNull()) {
                    throw new HttpException(400, sprintf('The property "%s" could not be extracted because it is not nullable.', $property));
                }

                $value = null;
            }
        }

        $this->data->set($property->name, $value);
    }
}
