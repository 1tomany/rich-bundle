<?php

namespace OneToMany\RichBundle\Tests\Action\Result;

use OneToMany\RichBundle\Action\Result\HandlerResult;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

use function array_key_last;
use function random_int;
use function time;

#[Group('UnitTests')]
#[Group('ActionTests')]
#[Group('ResultTests')]
final class HandlerResultTest extends TestCase
{
    public function testOkSets200HttpStatus(): void
    {
        $this->assertEquals(Response::HTTP_OK, HandlerResult::ok(true)->getStatus());
    }

    public function testCreatedSets201HttpStatus(): void
    {
        $this->assertEquals(Response::HTTP_CREATED, HandlerResult::created(true)->getStatus());
    }

    public function testInvokeReturnsResultData(): void
    {
        $resultData = (object) [
            'time' => time(),
        ];

        $this->assertSame($resultData, new HandlerResult($resultData)());
    }

    public function testGettingContextUsesGroupsSpecificallySetInTheContext(): void
    {
        $groups = ['read', 'read:children'];

        $context = [
            AbstractNormalizer::GROUPS => [
                'read', 'read:all', 'read:self',
            ],
        ];

        $this->assertArrayHasKey(AbstractNormalizer::GROUPS, $context);
        $this->assertNotSame($groups, $context[AbstractNormalizer::GROUPS]);

        $result = new HandlerResult(true)
            ->withContext($context)
            ->withGroups($groups);

        $this->assertSame($context, $result->getContext());
    }

    public function testGettingContextMergesGroupsWhenContextHasNoGroups(): void
    {
        $groups = ['group1', 'group2'];

        $context = [
            AbstractNormalizer::ATTRIBUTES => [
                'id', 'time', 'age', 'date',
            ],
        ];

        $this->assertArrayNotHasKey(AbstractNormalizer::GROUPS, $context);

        $result = new HandlerResult(true)
            ->withContext($context)
            ->withGroups($groups);

        $resultContext = $result->getContext();

        $this->assertArrayHasKey(AbstractNormalizer::GROUPS, $resultContext);
        $this->assertSame($groups, $resultContext[AbstractNormalizer::GROUPS]);
    }

    public function testWithStatusUsingInvalidHttpStatusSetsStatusToInternalServerError(): void
    {
        $lastHttpStatus = array_key_last(Response::$statusTexts);

        $httpStatus = random_int($lastHttpStatus + 1, $lastHttpStatus * 2);
        $this->assertArrayNotHasKey($httpStatus, Response::$statusTexts);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, new HandlerResult(true)->withStatus($httpStatus)->getStatus()); // @phpstan-ignore-line
    }
}
