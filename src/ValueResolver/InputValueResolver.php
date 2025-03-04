<?php

namespace OneToMany\RichBundle\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\RichInput;
use OneToMany\RichBundle\Attribute\RichProperty;
use OneToMany\RichBundle\Attribute\RichPropertyIgnored;
use OneToMany\RichBundle\ValueResolver\Exception\InvalidMappingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedContentException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
// use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use \ReflectionAttribute;
use \ReflectionClass;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class InputValueResolver implements ValueResolverInterface
{

    public function __construct(

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




        $class = new ReflectionClass($type);

        foreach ($class->getProperties() as $property) {
            // Ensure that the property is
            // a member of the class itself.
            $isSameClass = in_array($class->name, [
                $property->getDeclaringClass()->name,
            ]);

            if (!$isSameClass) {
                continue;
            }

            // If a property is explicitly marked as
            // ignored, don't attempt to extract it.
            $ignored = $property->getAttributes(
                RichPropertyIgnored::class
            );

            if (count($ignored)) {
                continue;
            }

            $sources = null;

            // Compile a list of PropertySource enums. This assumes
            // the order of the attributes on the property itself
            // is the priority that the data should be extracted.
            $richPropertyAttributes = $property->getAttributes(
                RichProperty::class, ReflectionAttribute::IS_INSTANCEOF
            );

            foreach ($richPropertyAttributes as $attribute) {
                $sources[] = $attribute->newInstance()->source;
            }

            // Ensure that if a property was not explicitly
            // ignored that it has at least one source. By
            // default, we assume it comes from the payload.
            $sources = $sources ?? [PropertySource::Payload];

            foreach ($sources as $source) {
                $dataExists = array_key_exists(
                    $property->name, $inputData
                );

                if ($dataExists) {
                    continue;
                }

                if (PropertySource::Query === $source && $request->query->has($property->name)) {
                    $inputData[$property->name] = $request->query->get($property->name);
                }

                if (PropertySource::Route === $source && array_key_exists($property->name, $routeParams)) {
                    $inputData[$property->name] = $routeParams[$property->name];
                }

                if (PropertySource::Payload === $source && array_key_exists($property->name, $payload)) {
                    $inputData[$property->name] = $payload[$property->name];
                }

                if (PropertySource::Token === $source && null !== $this->tokenStorage?->getToken()) {
                    $inputData[$property->name] = $this->tokenStorage->getToken()->getUserIdentifier();
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

        $attributes = new ReflectionClass($type)->getAttributes(
            RichInput::class, ReflectionAttribute::IS_INSTANCEOF
        );

        return (count($attributes) ? $type : null);
    }

}
