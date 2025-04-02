<?php

namespace OneToMany\RichBundle\Tests\Controller;

use OneToMany\RichBundle\Controller\ControllerResponse;
use OneToMany\RichBundle\Controller\Exception\InvalidHttpStatusException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ControllerTests')]
final class ControllerResponseTest extends TestCase
{
    public function testConstructorRequiresValidHttpStatusCode(): void
    {
        $this->expectException(InvalidHttpStatusException::class);

        // @phpstan-ignore-next-line
        new ControllerResponse(['id' => 1], random_int(1, 99));
    }

    public function testOkUsesCorrectHttpStatusCode(): void
    {
        $this->assertEquals(200, ControllerResponse::ok(true)->status);
    }

    public function testCreatedUsesCorrectHttpStatusCode(): void
    {
        $this->assertEquals(201, ControllerResponse::created(true)->status);
    }
}
