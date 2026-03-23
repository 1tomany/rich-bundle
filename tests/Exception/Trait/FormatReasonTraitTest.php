<?php

namespace OneToMany\RichBundle\Tests\Exception\Trait;

use OneToMany\RichBundle\Exception\Trait\FormatReasonTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ExceptionTests')]
final class FormatReasonTraitTest extends TestCase
{
    use FormatReasonTrait;

    public function testFormattingNullReasonReturnsEmptyString(): void
    {
        $this->assertEmpty($this->formatReason(null));
    }

    public function testFormattingEmptyTrimmedReasonReturnsEmptyString(): void
    {
        $this->assertEmpty($this->formatReason("\t    \n"));
    }

    public function testFormattingThrowableUsesMessage(): void
    {
        $this->assertEquals(': an error occurred', $this->formatReason(new \Exception('An error occurred.')));
    }
}
