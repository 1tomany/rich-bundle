<?php

namespace OneToMany\RichBundle\Tests\Contract;

use OneToMany\RichBundle\Contract\AbstractJsonSchema;

final readonly class TestSchema extends AbstractJsonSchema
{
    public static function schema(): array
    {
        $schema = [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'Test',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                ],
                'name' => [
                    'type' => 'string',
                ],
            ],
            'required' => [
                'id',
                'name',
            ],
            'additionalProperties' => false,
        ];

        return $schema;
    }
}
