<?php

namespace OneToMany\RichBundle\Tests\Action\Result;

use OneToMany\RichBundle\Action\Result\Result;
use OneToMany\RichBundle\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Group('UnitTests')]
#[Group('ActionTests')]
#[Group('ResultTests')]
final class ResultTest extends TestCase
{
    public function testOkSets200HttpStatus(): void
    {
        $this->assertEquals(Response::HTTP_OK, Result::ok(true)->getStatus());
    }

    public function testCreatedSets201HttpStatus(): void
    {
        $this->assertEquals(Response::HTTP_CREATED, Result::created(true)->getStatus());
    }

    public function testInvokeReturnsResultData(): void
    {
        $resultData = (object)[
            'time' => \time(),
        ];

        $this->assertSame($resultData, new Result($resultData)());
    }

    public function testGettingContextUsesGroupsSpecificallySetInTheContext(): void
    {
        $groups = ['read', 'read:children'];
        $contextGroups = ['read', 'read:all'];

        $this->assertNotSame($groups, $contextGroups);

        $context = [AbstractNormalizer::GROUPS => $contextGroups];
        $this->assertArrayHasKey(AbstractNormalizer::GROUPS, $context);

        $result = new Result(true)
            ->withContext($context)
            ->withGroups($groups);

        $this->assertSame($context, $result->getContext());
    }

    public function testGettingContextMergesGroupsWhenContextHasNoGroups(): void
    {
        $groups = ['group1', 'group2'];

        $context = [
            AbstractNormalizer::ATTRIBUTES => ['id', 'time'],
        ];
    }

    public function testWithStatusRequiresValidHttpStatus(): void
    {
        $lastHttpStatus = \array_key_last(
            Response::$statusTexts
        );

        $httpStatus = \random_int($lastHttpStatus + 1, $lastHttpStatus * 2);
        $this->assertArrayNotHasKey($httpStatus, Response::$statusTexts);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value {$httpStatus} is not a valid HTTP status code.");

        new Result(true)->withStatus($httpStatus); // @phpstan-ignore-line
    }
}
