<?php

namespace OneToMany\RichBundle\Tests\Input;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Input\InputParser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\ConstructorExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\ChainDecoder;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

#[Group('UnitTests')]
#[Group('InputTests')]
final class InputParserTest extends TestCase
{
    public function testParsingRequestRequiresContentTypeHeaderWithNonEmptyBody(): void
    {
        $this->expectExceptionObject(HttpException::create(422, 'Parsing the request failed because the Content-Type header was missing or malformed.'));

        $request = new Request(content: '{"id": 10}');
        $this->assertNotEmpty($request->getContent());

        $input = new class implements InputInterface
        {
            public function toCommand(): CommandInterface
            {
                throw new \RuntimeException('Not implemented!');
            }
        };

        $this->createInputParser()->parse($request, $input::class);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createInputParser(array $parameters = []): InputParserInterface
    {
        $containerBag = new ContainerBag(new Container(new ParameterBag($parameters)));

        $normalizers = [
            new BackedEnumNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
        ];

        $typeExtractors = [
            new ReflectionExtractor(),
            new ConstructorExtractor([
                new PhpDocExtractor(),
            ]),
        ];

        $normalizers[] = new ObjectNormalizer(null, null, null, new PropertyInfoExtractor([], [
            new ReflectionExtractor(), new ConstructorExtractor([new PhpDocExtractor()]),
        ]));

        $serializer = new Serializer($normalizers, [
            new JsonEncoder(), new XmlEncoder(),
        ]);

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        return new InputParser($containerBag, $serializer, $validator);
    }
}
