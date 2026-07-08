<?php

namespace OneToMany\RichBundle\Tests\ValueResolver;

use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Input\InputParser;
use OneToMany\RichBundle\ValueResolver\InputValueResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[Group('UnitTests')]
#[Group('ValueResolverTests')]
final class InputValueResolverTest extends TestCase
{
    public function testResolvingValueRequiresObjectToImplementInputInterface(): void
    {
        // Arrange: Create empty request
        $request = new Request([], [], content: null);

        // Arrange: Create argument
        $argument = $this->createArgument('string');

        // Assert: Argument is of type "string"
        $this->assertEquals('string', $argument->getType());

        // Assert: Argument is not of type "InputInterface"
        $this->assertNotEquals(InputInterface::class, $argument->getType());

        // Act: Resolve the value
        $values = $this->createValueResolver()->resolve($request, $argument);

        // Assert: No values resolved
        $this->assertCount(0, $values);
    }

    private function createArgument(?string $type = null): ArgumentMetadata
    {
        return new ArgumentMetadata('input', $type ?? InputInterface::class, false, false, null);
    }

    private function createValueResolver(): InputValueResolver
    {
        $inputParser = $this->createStub(InputParser::class);

        return new InputValueResolver($inputParser);
    }
}
