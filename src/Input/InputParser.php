<?php

namespace OneToMany\RichBundle\Input;

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
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

use function call_user_func;
use function count;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function strtolower;
use function trim;

readonly class InputParser implements InputParserInterface
{
    public function __construct(
        private ContainerBagInterface $containerBag,
        private DecoderInterface $decoder,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public function parse(Request $request, string $type, array $defaultData = []): ParameterBag
    {
        // Initialize the data source
        $inputData = new ParameterBag($defaultData);

        $format = strtolower($request->getContentTypeFormat() ?? '');

        // Content-Type: multipart/form-data
        if (in_array($format, ['form'])) {
            $requestData = $request->request->all();
        } else {
            $content = trim($request->getContent());

            if (!$format) {
                if (!empty($content)) {
                    throw new \RuntimeException('no content-type header'); // ResolutionFailedContentTypeHeaderNotFoundException();
                }
            }

            try {
                $requestData = $this->decoder->decode($content, $format);
            } catch (SerializerExceptionInterface $e) {
            }

            if (!is_array($requestData ?? null) || (($e ?? null) instanceof \Throwable)) {
                throw new \RuntimeException('no data', previous: ($e ?? null)); // ResolutionFailedDecodingContentFailedException($format, $e ?? null);
            }
        }

        $requestData = new ParameterBag($requestData);

        // Read the properties from the class
        $class = new \ReflectionClass($type);

        foreach ($class->getProperties() as $property) {
            // Skip explicitly ignored properties
            if ($this->isPropertyIgnored($property)) {
                continue;
            }

            foreach ($this->findSources($property) as $source) {
                $name = $source->getName($property->name);

                if ($inputData->has($name)) {
                    continue;
                }

                $value = null;

                if ($source instanceof SourceContainer) {
                    if (true === $this->containerBag->has($name)) {
                        $value = $this->containerBag->get($name);
                    }
                }

                if ($source instanceof SourceContent) {
                    $value = $request->getContent(false);
                }

                if ($source instanceof SourceFile) {
                    if (true === $request->files->has($name)) {
                        $value = $request->files->get($name);
                    }
                }

                if ($source instanceof SourceHeader) {
                    if (true === $request->headers->has($name)) {
                        $value = $request->headers->get($name);
                    }
                }

                if ($source instanceof SourceIpAddress) {
                    $value = $request->getClientIp();
                }

                if ($source instanceof SourceQuery) {
                    if (true === $request->query->has($name)) {
                        $value = $request->query->get($name);
                    }
                }

                if ($source instanceof SourceRequest) {
                    if (true === $requestData->has($name)) {
                        $value = $requestData->get($name);
                    }
                }

                if ($source instanceof SourceRoute) {
                    if (true === is_array($request->attributes->get('_route_params'))) {
                        $value = $request->attributes->get('_route_params')[$name] ?? null;
                    }
                }

                if ($source instanceof SourceUser) {
                    if (null === $this->tokenStorage) {
                        throw new \RuntimeException('no user token'); // ResolutionFailedSecurityBundleMissingException($property->name);
                    }

                    $value = $this->tokenStorage->getToken()?->getUser();
                }

                if (is_callable($callback = $source->callback)) {
                    $value = call_user_func($callback, $value);
                }

                // Trim the value if the source indicates to and it is a string
                $value = $source->trim && is_string($value) ? trim($value) : $value;

                // Ensure nullified sources support null property values
                if ($source->nullify && !$property->getType()?->allowsNull()) {
                    throw new \RuntimeException('property not nullable'); // ResolutionFailedPropertyNotNullableException($property->name);
                }

                // Nullify empty string values, leave other types alone
                $inputData->set($property->getName(), ($source->nullify && is_string($value) && empty($value)) ? null : $value);
            }
        }

        return $inputData;
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
}
