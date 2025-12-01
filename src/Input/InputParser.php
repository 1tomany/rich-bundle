<?php

namespace OneToMany\RichBundle\Input;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\SourceContainer;
use OneToMany\RichBundle\Attribute\SourceContent;
use OneToMany\RichBundle\Attribute\SourceFile;
use OneToMany\RichBundle\Attribute\SourceHeader;
use OneToMany\RichBundle\Attribute\SourceIpAddress;
use OneToMany\RichBundle\Attribute\SourcePayload;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Attribute\SourceRoute;
use OneToMany\RichBundle\Attribute\SourceUser;
use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Exception\LogicException;
use OneToMany\RichBundle\Validator\UninitializedProperties;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
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
use function is_array;
use function is_callable;
use function is_string;
use function sprintf;
use function trim;

readonly class InputParser implements InputParserInterface
{
    private ParameterBag $data;

    public function __construct(
        private ContainerBagInterface $containerBag,
        private SerializerInterface&DecoderInterface&DenormalizerInterface $serializer,
        private ValidatorInterface $validator,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
        $this->data = new ParameterBag([]);
    }

    /**
     * @template C of CommandInterface
     *
     * @return InputInterface<C>
     */
    public function parse(Request $request, string $type, array $defaultData = []): InputInterface
    {
        // Initialize the data
        $this->data->replace([]);

        // Immediately append all default values so
        // they aren't overwritten during parsing
        foreach ($defaultData as $key => $value) {
            $this->appendValue($key, $value);
        }

        // Decode the content based on the format
        $format = $request->getContentTypeFormat();

        if (in_array($format, ['form'])) {
            // application/x-www-form-urlencoded
            $payload = $request->request->all();
        } else {
            // application/json, application/xml
            if ($content = trim($request->getContent())) {
                if (!$format) {
                    throw HttpException::create(422, 'Parsing the request failed because the Content-Type header was missing or malformed.');
                }

                try {
                    $payload = $this->serializer->decode($content, $format);
                } catch (SerializerExceptionInterface $e) {
                }

                if (!is_array($payload ?? null) || (($e ?? null) instanceof \Throwable)) {
                    throw HttpException::create(400, sprintf('Parsing the request failed because the content could not be decoded as "%s".', $format), previous: ($e ?? null));
                }
            }
        }

        $payload = new ParameterBag($payload ?? []);

        // Read the properties from the class
        $class = new \ReflectionClass($type);

        foreach ($class->getProperties() as $property) {
            // Skip explicitly ignored properties
            if ($this->isPropertyIgnored($property)) {
                continue;
            }

            foreach ($this->findSources($property) as $source) {
                $name = $source->getName($property->getName());

                if ($source instanceof SourceContainer && $this->containerBag->has($name)) {
                    $this->appendProperty($property, $source, $this->containerBag->get($name));
                }

                if ($source instanceof SourceContent) {
                    $this->appendProperty($property, $source, $request->getContent());
                }

                if ($source instanceof SourceFile && $request->files->has($name)) {
                    $this->appendProperty($property, $source, $request->files->get($name));
                }

                if ($source instanceof SourceHeader && $request->headers->has($name)) {
                    $this->appendProperty($property, $source, $request->headers->get($name));
                }

                if ($source instanceof SourceIpAddress) {
                    $this->appendProperty($property, $source, $request->getClientIp());
                }

                if ($source instanceof SourcePayload && $payload->has($name)) {
                    $this->appendProperty($property, $source, $payload->get($name));
                }

                if ($source instanceof SourceRequest) {
                    $this->appendProperty($property, $source, $request);
                }

                if ($source instanceof SourceQuery && $request->query->has($name)) {
                    $this->appendProperty($property, $source, $request->query->all()[$name]);
                }

                if ($source instanceof SourceRoute) {
                    $routeParams ??= $request->attributes->get('_route_params');

                    if (is_array($routeParams) && isset($routeParams[$name])) {
                        $this->appendProperty($property, $source, $routeParams[$name]);
                    }
                }

                if ($source instanceof SourceUser) {
                    if (null === $this->tokenStorage) {
                        throw new LogicException(sprintf('Resolving the property "%s" failed because the Symfony Security Bundle is not installed. Try running "composer require symfony/security-bundle".', $property->getName()));
                    }

                    $this->appendProperty($property, $source, $this->tokenStorage->getToken()?->getUser());
                }
            }
        }

        try {
            /** @var InputInterface<C> $input */
            $input = $this->serializer->denormalize($this->data->all(), $type, null, [
                'filter_bool' => true, 'disable_type_enforcement' => true,
            ]);
        } catch (\Throwable $e) {
            throw HttpException::create(400, 'Parsing the request failed because it is is malformed and could not be mapped correctly.', previous: $e);
        }

        // Validate that all class properties are initialized.
        // Without it, validating the class will throw an error
        // because an uninitialized property can't be validated.
        $violations = $this->validator->validate($input, [
            new UninitializedProperties(),
        ]);

        if ($violations->count() > 0) {
            throw new ValidationFailedException($input, $violations);
        }

        return $input;
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

        return $propertySources ?? [new SourcePayload()];
    }

    private function appendProperty(\ReflectionProperty $property, PropertySource $source, mixed $value): void
    {
        // Ensure nullified sources support null property values
        if ($source->nullify && !$property->getType()?->allowsNull()) {
            throw HttpException::create(400, sprintf('Parsing the request failed because the property "%s" is not nullable.', $property->getName()));
        }

        // Apply any defined callback to the value first
        if (is_callable($callback = $source->callback)) {
            $value = call_user_func($callback, $value);
        }

        // Trim the value if the source indicates to and it is a string
        $value = $source->trim && is_string($value) ? trim($value) : $value;

        // Finally, convert empty string values to NULL, otherwise leave the value alone
        $this->appendValue($property->getName(), ($source->nullify && is_string($value) && empty($value)) ? null : $value);
    }

    private function appendValue(string $key, mixed $value): void
    {
        // Ignore values once mapped
        if ($this->data->has($key)) {
            return;
        }

        // Convert BackedEnum values into scalars so the enum normalizer doesn't complain
        $this->data->set($key, $value instanceof \BackedEnum ? $value->value : $value);
    }
}
