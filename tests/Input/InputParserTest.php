<?php

namespace OneToMany\RichBundle\Tests\Input;

use OneToMany\RichBundle\Attribute\PropertyIgnored;
use OneToMany\RichBundle\Attribute\SourceContent;
use OneToMany\RichBundle\Attribute\SourceHeader;
use OneToMany\RichBundle\Attribute\SourceQuery;
use OneToMany\RichBundle\Attribute\SourceRequest;
use OneToMany\RichBundle\Attribute\SourceUser;
use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use OneToMany\RichBundle\Exception\HttpException;
use OneToMany\RichBundle\Exception\LogicException;
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

use function json_encode;
use function random_int;
use function time;

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

    public function testParsingRequestDoesNotAttemptToDeserializeEmptyRequestContent(): void
    {
        $this->expectNotToPerformAssertions();

        $class = new class implements InputInterface {
            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $this->createInputParser()->parse(new Request(), $class::class);
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

    public function testParsingRequestRequiresDefaultValueIfValueNotMapped(): void
    {
        $this->expectException(ValidationFailedException::class);

        $class = new class implements InputInterface {
            #[SourceQuery]
            public string $name;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $request = new Request(query: []);
        $this->assertFalse($request->query->has('name'));

        $this->createInputParser()->parse($request, $class::class);
    }

    public function testParsingInputUsesDefaultValueIfNotMapped(): void
    {
        $class = new class implements InputInterface {
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

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($query['id'], $input->id);
        $this->assertEquals($class->name, $input->name);
    }

    public function testParsingRequestOverwritesDefaultValueIfMapped(): void
    {
        $class = new class implements InputInterface {
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
        $this->assertNotEquals($class->name, $query['name']);

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($query['id'], $input->id);
        $this->assertEquals($query['name'], $input->name);
    }

    public function testParsingRequestDoesNotOverwriteIgnoredPropertyDefaultValue(): void
    {
        $class = new class implements InputInterface {
            #[PropertyIgnored]
            public string $name = 'Modesto';

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $request = new Request(query: ['name' => 'Vic']);
        $this->assertTrue($request->query->has('name'));

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($class->name, $input->name);
    }

    public function testParsingRequestAllowsSourceToUseDifferentNameThanPropertyName(): void
    {
        $class = new class implements InputInterface {
            #[SourceQuery(name: 'userId')]
            public int $id;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $query = [
            'userId' => random_int(1, 100),
        ];

        $input = $this->createInputParser()->parse(new Request($query), $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($query['userId'], $input->id);
    }

    public function testParsingRequestCallsDefinedCallbackFunction(): void
    {
        $class = new class implements InputInterface {
            #[SourceQuery(callback: 'str_rot13')]
            public string $name;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $name = 'Vic Cherubini';
        $nameRot13 = \str_rot13($name);

        $input = $this->createInputParser()->parse(new Request(['name' => $name]), $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($nameRot13, $input->name);
    }

    public function testParsingRequestTrimsNonNullScalarValues(): void
    {
        $class = new class implements InputInterface {
            #[SourceQuery(trim: true)]
            public int $id;

            #[SourceQuery(trim: true)]
            public string $name;

            #[SourceQuery(trim: true)]
            public \DateTimeImmutable $dob;

            #[SourceQuery(trim: false)]
            public string $notes;

            #[PropertyIgnored]
            public string $birthday {
                get => $this->dob->format('F d, Y');
            }

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $query = [
            'id' => random_int(1, 100),
            'name' => 'Vic Cherubini',
            'dob' => 'August 25, 1984',
            'notes' => ' Great programmer ',
        ];

        $this->assertStringStartsWith(' ', $query['notes']);
        $this->assertStringEndsWith(' ', $query['notes']);

        $request = new Request(query: [
            'id' => " {$query['id']} ",
            'name' => " {$query['name']} ",
            'dob' => " {$query['dob']} ",
            'notes' => $query['notes'],
        ]);

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($query['id'], $input->id);
        $this->assertEquals($query['name'], $input->name);
        $this->assertEquals($query['dob'], $input->birthday);
        $this->assertEquals($query['notes'], $input->notes);
    }

    public function testParsingRequestRequiresPropertiesToAllowNullsIfNullified(): void
    {
        $this->expectExceptionObject(HttpException::create(400, 'Parsing the request failed because the property "name" is not nullable.'));

        $class = new class implements InputInterface {
            #[SourceQuery(trim: true, nullify: true)]
            public string $name;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $this->createInputParser()->parse(new Request(['name' => ' ']), $class::class);
    }

    public function testParsingRequestDoesNotNullifyEmptyIntegerProperties(): void
    {
        $class = new class implements InputInterface {
            #[SourceQuery(nullify: true)]
            public ?int $uId;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $query = ['uId' => 0];
        $input = $this->createInputParser()->parse(new Request($query), $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertSame($query['uId'], $input->uId);
    }

    public function testParsingRequestNullifiesNullablePropertiesIfValueIsEmptyString(): void
    {
        $class = new class implements InputInterface {
            #[SourceQuery]
            public int $id;

            #[SourceQuery(nullify: true)]
            public ?int $age;

            #[SourceQuery(nullify: true)]
            public ?string $name;

            #[SourceQuery(nullify: true)]
            public ?string $color;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $query = [
            'id' => random_int(1, 100),
            'age' => null,
            'name' => '',
            'color' => ' ',
        ];

        $request = new Request(query: $query);

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($query['id'], $input->id);

        $this->assertNull($input->age);
        $this->assertNull($input->name);
        $this->assertNull($input->color);
    }

    public function testParsingSourceContent(): void
    {
        $class = new class implements InputInterface {
            #[SourceContent]
            public string $content;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        /** @var non-empty-string $content */
        $content = json_encode(['time' => time()]);
        $request = new Request(server: ['CONTENT_TYPE' => 'application/json'], content: $content);

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($content, $input->content);
    }

    public function testParsingSourceHeader(): void
    {
        $faker = \Faker\Factory::create();

        $class = new class implements InputInterface {
            #[SourceHeader(name: 'ACCEPT')]
            public ?string $accept;

            #[SourceHeader(name: 'content-type')]
            public ?string $type;

            #[SourceHeader(name: 'x-user')]
            public ?string $userId;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $server = [
            'HTTP_ACCEPT' => $faker->mimeType(),
            'CONTENT_TYPE' => $faker->mimeType(),
            'HTTP_X_USER' => $faker->sha256(),
        ];

        $input = $this->createInputParser()->parse(new Request(server: $server), $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($server['HTTP_ACCEPT'], $input->accept);
        $this->assertEquals($server['CONTENT_TYPE'], $input->type);
        $this->assertEquals($server['HTTP_X_USER'], $input->userId);
    }

    public function testParsingSourceUserRequiresSymfonySecurityBundle(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Symfony Security Bundle is not installed/');

        $class = new class implements InputInterface {
            public function __construct(
                #[SourceUser]
                public ?UserInterface $user = null,
            ) {
            }

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $this->createInputParser()->parse(new Request(), $class::class);
    }

    public function testParsingSourceRequestUsingFormData(): void
    {
        $class = new class implements InputInterface {
            #[SourceRequest]
            public string $name;

            #[SourceRequest]
            public string $email;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $data = [
            'name' => 'Vic Cherubini',
            'email' => 'vcherubini@gmail.com',
        ];

        $request = new Request(request: $data, server: [
            'CONTENT_TYPE' => 'multipart/form-data',
        ]);

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($data['name'], $input->name);
        $this->assertEquals($data['email'], $input->email);
    }

    public function testParsingSourceRequestUsingJsonData(): void
    {
        $class = new class implements InputInterface {
            #[SourceRequest]
            public string $name;

            #[SourceRequest]
            public string $email;

            public function toCommand(): CommandInterface
            {
                throw new \Exception('Not implemented!');
            }
        };

        $data = [
            'name' => 'Vic Cherubini',
            'email' => 'vcherubini@gmail.com',
        ];

        $request = new Request(content: (string) json_encode($data));
        $request->headers->set('Content-Type', 'application/json');

        $input = $this->createInputParser()->parse($request, $class::class);

        $this->assertInstanceOf($class::class, $input);
        $this->assertEquals($data['name'], $input->name);
        $this->assertEquals($data['email'], $input->email);
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
