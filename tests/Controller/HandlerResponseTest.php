<?php

namespace OneToMany\RichBundle\Tests\Controller;

use OneToMany\RichBundle\Controller\HandlerResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ControllerTests')]
final class HandlerResponseTest extends TestCase
{

    public function testOkUsesCorrectHttpStatusCode(): void
    {
        $this->assertEquals(200, HandlerResponse::ok(true)->status);
    }

    public function testCreatedUsesCorrectHttpStatusCode(): void
    {
        $this->assertEquals(201, HandlerResponse::created(true)->status);
    }

}