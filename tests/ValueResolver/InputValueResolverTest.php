<?php

namespace OneToMany\RichBundle\Tests\ValueResolver;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\SourceContent;
use OneToMany\RichBundle\Attribute\SourceHeader;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Attribute\SourceUser;
use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedContentTypeHeaderNotFoundException;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedDecodingContentFailedException;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedPropertyNotNullableException;
use OneToMany\RichBundle\ValueResolver\Exception\ResolutionFailedSecurityBundleMissingException;
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
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Security\Core\User\UserInterface;
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





























    private function createArgument(?string $type = null): ArgumentMetadata
    {
        return new ArgumentMetadata('input', $type ?? InputInterface::class, false, false, null);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createValueResolver(array $parameters = []): InputValueResolver
    {
        $parameters = new ParameterBag(...[
            'parameters' => $parameters,
        ]);

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
            new ReflectionExtractor(),
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

        return new InputValueResolver(new ContainerBag(new Container($parameters)), new Serializer($normalizers, $encoders), Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }
}
