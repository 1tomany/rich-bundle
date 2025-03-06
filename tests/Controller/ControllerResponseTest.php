<?php

namespace OneToMany\RichBundle\Tests\Controller;

use OneToMany\RichBundle\Controller\ControllerResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ControllerTests')]
final class ControllerResponseTest extends TestCase
{

    public function testOkUsesCorrectHttpStatusCode(): void
    {
        $this->assertEquals(200, ControllerResponse::ok(true)->status);
    }

    public function testCreatedUsesCorrectHttpStatusCode(): void
    {
        $this->assertEquals(201, ControllerResponse::created(true)->status);
    }

}
