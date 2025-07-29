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
use function time;

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
        $content = json_encode(['time' => time()]);

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
                #[SourceUser]
                public ?UserInterface $user = null,
            ) {
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
