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

use function json_encode;
use function random_int;

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
        $this->expectException(ResolutionFailedContentTypeHeaderNotFoundException::class);

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
        $this->expectException(ResolutionFailedDecodingContentFailedException::class);

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
    public function testResolvingValueRequiresNonMalformedContent(string $contentType, string $content): void
    {
        $this->expectException(ResolutionFailedDecodingContentFailedException::class);

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
            ['text/xml', '<?xml version="1.0" encoding="UTF-8"?><root><id>10</id>'],
            ['application/xml', '<?xml><root><id>10</root>'],
            ['application/json', '{"id": 10, "name: "Marcus Wolffe"}'],
        ];

        return $provider;
    }

    public function testResolvingPropertiesRequiresDefaultValueIfValueNotMapped(): void
    {
        $this->expectException(ValidationFailedException::class);

        $input = new class implements InputInterface {
            #[SourceQuery]
            public string $name;

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $id = random_int(1, 100);

        $request = new Request(...[
            'query' => [
                'id' => $id,
            ],
        ]);

        $this->assertFalse($request->query->has('name'));

        $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );
    }

    public function testResolvingPropertiesUsesDefaultValueIfNotMapped(): void
    {
        $input = new class implements InputInterface {
            #[SourceQuery]
            public int $id;

            #[SourceQuery]
            public string $name = 'Modesto';

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $id = random_int(1, 100);
        $name = new \ReflectionProperty($input, 'name')->getDefaultValue();

        $request = new Request(...[
            'query' => [
                'id' => $id,
            ],
        ]);

        $this->assertTrue($request->query->has('id'));
        $this->assertFalse($request->query->has('name'));

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);
        $this->assertEquals($id, $inputs[0]->id);
        $this->assertEquals($name, $inputs[0]->name);
    }

    public function testResolvingPropertiesOverwritesDefaultValueIfMapped(): void
    {
        $input = new class implements InputInterface {
            #[SourceQuery]
            public int $id;

            #[SourceQuery]
            public string $name = 'Modesto';

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $id = random_int(1, 100);
        $name = 'Vic Cherubini';

        $request = new Request(...[
            'query' => [
                'id' => $id,
                'name' => $name,
            ],
        ]);

        $this->assertTrue($request->query->has('id'));
        $this->assertTrue($request->query->has('name'));

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);
        $this->assertEquals($id, $inputs[0]->id);
        $this->assertEquals($name, $inputs[0]->name);
    }

    public function testResolvingIgnoredPropertiesDoesNotOverwriteDefaultPropertyValue(): void
    {
        $input = new class implements InputInterface {
            #[PropertyIgnored]
            public string $name = 'Modesto';

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $name = 'Vic Cherubini';

        $request = new Request(...[
            'query' => [
                'name' => $name,
            ],
        ]);

        $this->assertTrue($request->query->has('name'));

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);
        $this->assertNotEquals($name, $inputs[0]->name);
    }

    public function testTrimmingNonNullScalarValues(): void
    {
        $input = new class implements InputInterface {
            #[SourceQuery(trim: true)]
            public int $id;

            #[SourceQuery(trim: true)]
            public string $name;

            #[SourceQuery(trim: true)]
            public \DateTimeImmutable $dob;

            #[PropertyIgnored]
            public string $birthday {
                get => $this->dob->format('Y-m-d');
            }

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $id = random_int(1, 100);
        $name = 'Vic Cherubini';
        $dob = '1984-08-25';

        $request = new Request(...[
            'query' => [
                'id' => " {$id} ",
                'name' => " {$name} ",
                'dob' => " {$dob} ",
            ],
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);
        $this->assertEquals($id, $inputs[0]->id);
        $this->assertEquals($name, $inputs[0]->name);
        $this->assertEquals($dob, $inputs[0]->birthday);
    }

    public function testResolvingPropertiesRequiresThemToAllowNullsIfNullable(): void
    {
        $this->expectException(ResolutionFailedPropertyNotNullableException::class);

        $input = new class implements InputInterface {
            #[SourceQuery(nullify: true)]
            public string $name;

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $request = new Request(...[
            'query' => [
                'name' => ' ',
            ],
        ]);

        $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );
    }

    public function testResolvingNullablePropertiesNullifiesThemIfValueIsEmptyString(): void
    {
        $input = new class implements InputInterface {
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
                return new class implements CommandInterface {};
            }
        };

        $id = random_int(1, 100);

        $request = new Request(...[
            'query' => [
                'id' => $id,
                'age' => null,
                'name' => '',
                'color' => '  ',
            ],
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);
        $this->assertEquals($id, $inputs[0]->id);
        $this->assertNull($inputs[0]->age);
        $this->assertNull($inputs[0]->name);
        $this->assertNull($inputs[0]->color);
    }

    public function testResolvingSourceContent(): void
    {
        $input = new class implements InputInterface {
            #[SourceContent]
            public string $content;

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        /** @var non-empty-string $content */
        $content = json_encode(['time' => \time()]);

        $request = new Request(...[
            'server' => [
                'HTTP_CONTENT_TYPE' => 'application/json',
            ],
            'content' => $content,
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);
        $this->assertEquals($content, $inputs[0]->content);
    }

    public function testResolvingSourceHeader(): void
    {
        $faker = \Faker\Factory::create();

        $input = new class implements InputInterface {
            #[SourceHeader(name: 'ACCEPT')]
            public ?string $accept;

            #[SourceHeader(name: 'content-type')]
            public ?string $type;

            #[SourceHeader(name: 'X-Custom-Id')]
            public ?string $customId;

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $request = new Request(...[
            'server' => [
                'HTTP_ACCEPT' => $faker->mimeType(),
                'HTTP_CONTENT_TYPE' => $faker->mimeType(),
                'HTTP_X_CUSTOM_ID' => $faker->sha256(),
            ],
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);

        $headers = $request->headers->all();
        $this->assertEquals($headers['accept'][0], $inputs[0]->accept);
        $this->assertEquals($headers['content-type'][0], $inputs[0]->type);
        $this->assertEquals($headers['x-custom-id'][0], $inputs[0]->customId);
    }

    public function testResolvingSourceUserRequiresSymfonySecurityBundle(): void
    {
        $this->expectException(ResolutionFailedSecurityBundleMissingException::class);

        // Arrange: Create Input Class
        $input = new class implements InputInterface {
            public function __construct(
                #[SourceUser(self::class)] // @phpstan-ignore argument.type
                public ?int $userId = null,
            )
            {
            }

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        // Arrange: Create Value Resolver Without TokenStorageInterface
        $valueResolver = new InputValueResolver(new ContainerBag(new Container(null)), new Serializer([], [], []), Validation::createValidator());

        // Assert: $tokenStorage Property Is Null
        $refProperty = new \ReflectionProperty($valueResolver, 'tokenStorage');
        $refProperty->setAccessible(true);

        $this->assertNull($refProperty->getValue($valueResolver));

        // Assert: Resolving SourceUser Property Requires TokenStorage
        $valueResolver->resolve(new Request(), $this->createArgument($input::class));
    }

    public function testResolverDoesNotAttemptToDeserializeEmptyRequestContent(): void
    {
        $input = new class implements InputInterface {
            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $request = new Request(...[
            'server' => [
                'HTTP_CONTENT_TYPE' => 'text/javascript',
            ],
        ]);

        $this->assertEmpty($request->getContent());
        $this->assertNotNull($request->getContentTypeFormat());

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);
    }

    public function testResolvingPropertiesFromMultipartFormDataRequest(): void
    {
        $faker = \Faker\Factory::create();

        $input = new class implements InputInterface {
            #[SourceRequest]
            public string $name;

            #[SourceRequest]
            public string $email;

            public function toCommand(): CommandInterface
            {
                return new class implements CommandInterface {};
            }
        };

        $request = new Request(...[
            'request' => [
                'name' => $faker->name(),
                'email' => $faker->email(),
            ],
            'server' => [
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        ]);

        $inputs = $this->createValueResolver()->resolve(
            $request, $this->createArgument($input::class)
        );

        $this->assertInstanceOf($input::class, $inputs[0]);

        $request = $request->request->all();
        $this->assertEquals($request['name'], $inputs[0]->name);
        $this->assertEquals($request['email'], $inputs[0]->email);
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
