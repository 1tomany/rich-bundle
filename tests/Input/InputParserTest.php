<?php

namespace OneToMany\RichBundle\Tests\Input;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Input\InputParser;
use PHPUnit\Framework\Attributes\DataProvider;
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
use Symfony\Component\Validator\Exception\ValidationFailedException;
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

        $this->createInputParser()->parse($request, InputInterface::class);
    }

    public function testParsingRequestRequiresValidFormatAndDecoder(): void
    {
        $this->expectExceptionObject(HttpException::create(400, 'Parsing the request failed because the content could not be decoded as "txt".'));

        $request = new Request(...[
            'server' => [
                'CONTENT_TYPE' => 'text/plain',
            ],
            'content' => 'Pipe|delmited|format',
        ]);

        $this->assertNotEmpty($request->getContent());
        $this->assertNotEmpty($request->getContentTypeFormat());

        $this->createInputParser()->parse($request, InputInterface::class);
    }

    #[DataProvider('providerContentTypeAndMalformedContent')]
    public function testParsingRequestRequiresNonMalformedContent(string $contentType, string $content): void
    {
        $request = new Request(server: ['CONTENT_TYPE' => $contentType], content: $content);

        $this->assertNotEmpty($request->getContent());
        $this->assertNotEmpty($request->getContentTypeFormat());

        $this->expectExceptionObject(HttpException::create(400, 'Parsing the request failed because the content could not be decoded as "'.$request->getContentTypeFormat().'".'));

        $this->createInputParser()->parse($request, InputInterface::class);
    }

    public function testParsingRequestRequiresDefaultValueIfValueNotMapped(): void
    {
        $this->expectException(ValidationFailedException::class);

        $input = new class implements InputInterface {
            #[SourceQuery]
            public string $name;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $request = new Request(query: []);
        $this->assertFalse($request->query->has('name'));

        $this->createInputParser()->parse($request, $input::class);
    }

    public function testParsingInputUsesDefaultValueIfNotMapped(): void
    {
        $input = new class implements InputInterface {
            #[SourceQuery]
            public int $id;

            #[SourceQuery]
            public string $name = 'Modesto';

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $query = ['id' => random_int(1, 100)];
        $request = new Request(query: $query);

        $this->assertTrue($request->query->has('id'));
        $this->assertFalse($request->query->has('name'));

        $result = $this->createInputParser()->parse($request, $input::class);

        $this->assertInstanceOf($input::class, $result);
        $this->assertEquals($query['id'], $result->id);
        $this->assertEquals($input->name, $result->name);
    }

    public function testParsingRequestOverwritesDefaultValueIfMapped(): void
    {
        $input = new class implements InputInterface {
            #[SourceQuery]
            public int $id;

            #[SourceQuery]
            public string $name = 'Modesto';

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $query = [
            'id' => random_int(1, 100),
            'name' => 'Vic Cherubini',
        ];

        $request = new Request(query: $query);

        $this->assertTrue($request->query->has('id'));
        $this->assertTrue($request->query->has('name'));
        $this->assertNotEquals($input->name, $query['name']);

        $result = $this->createInputParser()->parse($request, $input::class);

        $this->assertInstanceOf($input::class, $result);
        $this->assertEquals($query['id'], $result->id);
        $this->assertEquals($query['name'], $result->name);
    }

    public function testParsingRequestDoesNotOverwriteIgnoredPropertyDefaultValue(): void
    {
        $input = new class implements InputInterface {
            #[PropertyIgnored]
            public string $name = 'Modesto';

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $request = new Request(query: ['name' => 'Vic']);
        $this->assertTrue($request->query->has('name'));

        $result = $this->createInputParser()->parse($request, $input::class);

        $this->assertInstanceOf($input::class, $result);
        $this->assertEquals($input->name, $result->name);
    }


    /**
     * @return list<list<non-empty-string>>
     */
    public static function providerContentTypeAndMalformedContent(): array
    {
        $provider = [
            ['text/xml', '<?xml version="1.0" encoding="UTF-8"?><root><id>10</id>'],
            ['application/xml', '<?xml><root><id>10</root>'],
            ['application/json', '{"id": 10, "name: "Marcus Wolffe"}'],
        ];

        return $provider;
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
