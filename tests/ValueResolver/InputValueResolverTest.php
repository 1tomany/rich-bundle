<?php

namespace OneToMany\RichBundle\Tests\ValueResolver;

use OneToMany\RichBundle\Tests\ValueResolver\Fixture\EmptyInput;
use OneToMany\RichBundle\Tests\ValueResolver\Fixture\IgnoredInput;
use OneToMany\RichBundle\Tests\ValueResolver\Fixture\NotMappedInput;
use OneToMany\RichBundle\ValueResolver\Exception\MalformedRequestContentException;
use OneToMany\RichBundle\ValueResolver\Exception\PropertySourceNotMappedException;
use OneToMany\RichBundle\ValueResolver\InputValueResolver;
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
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
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

    public function testResolvingValueRequiresValidJson(): void
    {
        $this->expectException(MalformedRequestContentException::class);

        $request = new Request(...[
            'content' => '{"malformed: JSON, }',
        ]);

        $this->assertNotEmpty($request->getContent());

        $this->createValueResolver()->resolve(
            $request, $this->createArgument()
        );
    }

    public function testResolvingNonPromotedPropertiesRequiresDefaultValueIfValueNotMapped(): void
    {
        $this->expectException(PropertySourceNotMappedException::class);

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

    public function testResolvingIgnoredPropertiesDoesNotOverwritePropertyValue(): void
    {
        $nameFromRequest = 'Vic';

        $request = new Request(query: [
            'name' => $nameFromRequest,
        ]);

        $this->assertTrue($request->query->has('name'));

        $ignoredArgument = $this->createArgument(...[
            'type' => IgnoredInput::class,
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $ignoredArgument
        );

        $this->assertInstanceOf(IgnoredInput::class, $inputs[0]);
        $this->assertNotEquals($nameFromRequest, $inputs[0]->name);
    }

    public function testResolvingPropertiesFromSingleSource(): void
    {
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
        ]);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return new InputValueResolver($containerBag, $serializer, $validator);
    }
}
