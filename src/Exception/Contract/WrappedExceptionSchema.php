<?php

namespace OneToMany\RichBundle\Exception\Contract;

use OneToMany\RichBundle\Contract\AbstractJsonSchema;

final readonly class WrappedExceptionSchema extends AbstractJsonSchema
{
    public static function schema(): array
    {
        return [
            'title' => 'WrappedException',
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'integer',
                    'minimum' => 100,
                    'maximum' => 599,
                ],
                'title' => [
                    'type' => 'string',
                ],
                'detail' => [
                    'type' => 'string',
                ],
                'violations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'property' => [
                                'type' => 'string',
                            ],
                            'message' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'property',
                            'message',
                        ],
                        'additionalProperties' => false,
                    ],
                ],
                'stack' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'class' => [
                                'type' => 'string',
                            ],
                            'message' => [
                                'type' => 'string',
                            ],
                            'file' => [
                                'type' => 'string',
                            ],
                            'line' => [
                                'type' => 'integer',
                                'minimum' => 0,
                            ],
                        ],
                        'required' => [
                            'class',
                            'message',
                            'file',
                            'line',
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => [
                'status',
                'title',
                'detail',
                'violations',
            ],
            'additionalProperties' => false,
        ];
    }
}
