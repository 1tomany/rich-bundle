<?php

namespace OneToMany\RichBundle\Tests\ValueResolver;

use OneToMany\RichBundle\Tests\ValueResolver\Fixture\EmptyInput;
use OneToMany\RichBundle\Tests\ValueResolver\Fixture\IgnoredInput;
use OneToMany\RichBundle\Tests\ValueResolver\Fixture\NotMappedInput;
use OneToMany\RichBundle\Tests\ValueResolver\Fixture\PartiallyMappedInput;
use OneToMany\RichBundle\Tests\ValueResolver\Fixture\SourceRequestInput;
use OneToMany\RichBundle\ValueResolver\Exception\ContentTypeHeaderMissingException;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedRequestContentException;
use OneToMany\RichBundle\ValueResolver\InputValueResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyInfo\Extractor\ConstructorExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
final class InputValueResolverTest extends TestCase
{
    public function testResolvingValueRequiresObjectToImplementInputInterface(): void
    {
        $arguments = $this->createValueResolver()->resolve(
            new Request(), $this->createArgument('string')
        );

        $this->assertCount(0, $arguments);
    }

    public function testResolvingValueRequiresContentTypeHeaderWithNonEmptyBody(): void
    {
        $this->expectException(ContentTypeHeaderMissingException::class);

        $request = new Request(...[
            'content' => '{"id": 10}',
        ]);

        $this->assertNotEmpty($request->getContent());
        $this->assertEmpty($request->headers->get('CONTENT_TYPE'));

        $this->createValueResolver()->resolve(
            $request, $this->createArgument()
        );
    }

    public function testResolvingValueRequiresValidFormatAndDecoder(): void
    {
        $this->expectException(MalformedRequestContentException::class);

        $request = new Request(...[
            'server' => [
                'CONTENT_TYPE' => 'text/plain',
            ],
            'content' => 'My|pipe|delmited|format',
        ]);

        $this->assertNotEmpty($request->getContent());
        $this->assertNotEmpty($request->getContentTypeFormat());

        $this->createValueResolver()->resolve(
            $request, $this->createArgument()
        );
    }

    #[DataProvider('providerContentTypeAndMalformedContent')]
    public function testResolvingValueRequiresNonMalformedContent(string $contentType, string $format, string $content): void
    {
        $this->expectException(MalformedRequestContentException::class);
        $this->expectExceptionMessage('The request content is expected to be "'.$format.'" but could not be decoded because it is malformed.');

        $request = new Request(...[
            'server' => [
                'CONTENT_TYPE' => $contentType,
            ],
            'content' => $content,
        ]);

        $this->assertNotEmpty($request->getContent());
        $this->assertNotEmpty($request->getContentTypeFormat());

        $this->createValueResolver()->resolve(
            $request, $this->createArgument()
        );
    }

    /**
     * @return list<list<non-empty-string>>
     */
    public static function providerContentTypeAndMalformedContent(): array
    {
        $provider = [
            ['text/xml', 'xml', '<?xml version="1.0" encoding="UTF-8"?><root><id>10</id>'],
            ['application/xml', 'xml', '<?xml><root><id>10</root>'],
            ['application/json', 'json', '{"id": 10, "name: "Marcus Wolffe"}'],
        ];

        return $provider;
    }

    public function testResolvingPropertiesRequiresDefaultValueIfValueNotMapped(): void
    {
        $this->expectException(ValidationFailedException::class);

        $request = new Request(...[
            'query' => ['id' => 10],
        ]);

        $this->assertFalse($request->query->has('name'));

        $notMappedArgument = $this->createArgument(...[
            'type' => NotMappedInput::class,
        ]);

        $this->createValueResolver()->resolve(
            $request, $notMappedArgument
        );
    }

    public function testResolvingPropertiesUsesDefaultValueIfNotMapped(): void
    {
        $refProp = new \ReflectionProperty(
            PartiallyMappedInput::class, 'name'
        );

        $request = new Request(...[
            'query' => [
                'id' => 10,
            ],
        ]);

        $this->assertTrue($request->query->has('id'));
        $this->assertFalse($request->query->has('name'));

        $partiallyMappedArgument = $this->createArgument(...[
            'type' => PartiallyMappedInput::class,
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $partiallyMappedArgument
        );

        $this->assertInstanceOf(PartiallyMappedInput::class, $inputs[0]);
        $this->assertEquals($request->query->get('id'), $inputs[0]->id);
        $this->assertEquals($refProp->getDefaultValue(), $inputs[0]->name);
    }

    public function testResolvingPropertiesOverwritesDefaultValueIfMapped(): void
    {
        $request = new Request(...[
            'query' => [
                'id' => 10,
                'name' => 'Vic',
            ],
        ]);

        $this->assertTrue($request->query->has('id'));
        $this->assertTrue($request->query->has('name'));

        $partiallyMappedArgument = $this->createArgument(...[
            'type' => PartiallyMappedInput::class,
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $partiallyMappedArgument
        );

        $this->assertInstanceOf(PartiallyMappedInput::class, $inputs[0]);
        $this->assertEquals($request->query->get('id'), $inputs[0]->id);
        $this->assertEquals($request->query->get('name'), $inputs[0]->name);
    }

    public function testResolvingIgnoredPropertiesDoesNotOverwritePropertyValue(): void
    {
        $request = new Request(...[
            'query' => [
                'name' => 'Vic',
            ],
        ]);

        $this->assertTrue($request->query->has('name'));

        $ignoredArgument = $this->createArgument(...[
            'type' => IgnoredInput::class,
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $ignoredArgument
        );

        $this->assertInstanceOf(IgnoredInput::class, $inputs[0]);
        $this->assertNotEquals($request->query->get('name'), $inputs[0]->name);
    }

    public function testResolvingPropertiesFromMultipartFormDataRequest(): void
    {
        $formData = [
            'name' => 'Vic',
            'age' => 40,
            'email' => 'vcherubini@gmail.com',
            'height' => 74,
        ];

        $request = new Request(...[
            'request' => $formData,
            'server' => [
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        ]);

        $argumentMetadata = $this->createArgument(...[
            'type' => SourceRequestInput::class,
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $argumentMetadata
        );

        $this->assertInstanceOf(SourceRequestInput::class, $inputs[0]);
        $this->assertEquals($formData['name'], $inputs[0]->name);
        $this->assertEquals($formData['age'], $inputs[0]->age);
        $this->assertEquals($formData['email'], $inputs[0]->email);
    }

    private function createArgument(string $type = EmptyInput::class): ArgumentMetadata
    {
        return new ArgumentMetadata('input', $type, false, false, null);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createValueResolver(array $parameters = []): InputValueResolver
    {
        $parameters = new ParameterBag(...[
            'parameters' => $parameters,
        ]);

        $containerBag = new ContainerBag(
            new Container($parameters)
        );

        // Default encoders
        $encoders = [
            new JsonEncoder(),
            new XmlEncoder(),
        ];

        // Default normalizers
        $normalizers = [
            new BackedEnumNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
        ];

        // Property type extractors
        $propertyTypeExtractors = [
            new ConstructorExtractor([
                new PhpDocExtractor(),
            ]),
        ];

        $typeExtractor = new PropertyInfoExtractor(...[
            'typeExtractors' => $propertyTypeExtractors,
        ]);

        $normalizers[] = new ObjectNormalizer(...[
            'propertyTypeExtractor' => $typeExtractor,
        ]);

        $serializer = new Serializer(...[
            'normalizers' => $normalizers,
            'encoders' => $encoders,
        ]);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return new InputValueResolver($containerBag, $serializer, $validator);
    }
}
