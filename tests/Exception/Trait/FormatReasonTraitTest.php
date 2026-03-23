<?php

namespace OneToMany\RichBundle\Tests\Exception\Trait;

use OneToMany\RichBundle\Exception\Trait\FormatReasonTrait;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertEquals('an error occurred', $this->formatReason(new \Exception('An error occurred.'), ''));
    }

    #[DataProvider('providerReasonAndFormattedReason')]
    public function testFormattingReasonIterativelyRemovesEndingPunctuation(
        string $reason,
        string $formattedReason,
    ): void
    {
        $this->assertEquals($formattedReason, $this->formatReason($reason, ''));
    }

    /**
     * @return non-empty-list<non-empty-list<string>>
     */
    public static function providerReasonAndFormattedReason(): array
    {
        $provider = [
            ['', ''],
            [' ', ''],
            ['.', ''],
            ['...', ''],
            ['a...', 'a'],
            ['A.,!;', 'a'],
            ['Error!!!', 'error'],
            ['Error; file not found.', 'error; file not found'],
            ['Account: not  found;', 'account: not  found'],
        ];

        return $provider;
    }
}
