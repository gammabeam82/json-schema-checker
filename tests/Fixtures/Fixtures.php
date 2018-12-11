<?php

namespace Tests\Fixtures;

class Fixtures
{
    public const PRODUCT_SCHEMA = [
        'id' => 'integer',
        'name' => 'string'
    ];

    public const CATEGORY_WITH_PRODUCTS_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'products' => [
            'id' => 'integer',
            'name' => 'string',
            'images' => [
                'id' => 'integer',
                'filename' => 'string'
            ]
        ]
    ];

    public const CATEGORY_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'products' => [
            'nullable' => true,
            'id' => 'integer',
            'name' => 'string'
        ]
    ];

    public const USER_SCHEMA = [
        'id' => 'integer',
        'username' => 'string',
        'avatar' => 'string|nullable',
        'roles' => ['string|nullable'],
        'enabled' => 'boolean'
    ];

    public const PRODUCT = [
        'id' => 1,
        'name' => 'test product'
    ];

    public const INVALID_TYPE_PRODUCT = [
        'id' => 'abc',
        'name' => 'test product'
    ];

    public const MISSING_KEY_PRODUCT = [
        'id' => 1
    ];

    public const CATEGORY = [
        'id' => 1,
        'name' => 'test category',
        'products' => []
    ];

    public const CATEGORY_WITH_PRODUCTS = [
        'id' => 1,
        'name' => 'test category',
        'products' => [
            [
                'id' => 1,
                'name' => 'test product',
                'images' => [
                    [
                        'id' => 1,
                        'filename' => 'test.png'
                    ]
                ]
            ]
        ]
    ];

    public const USER = [
        'id' => 1,
        'username' => 'test',
        'avatar' => 'pic.jpg',
        'roles' => ['ROLE_ADMIN'],
        'enabled' => true
    ];

    public const USER_WITHOUT_ROLES = [
        'id' => 1,
        'username' => 'test',
        'avatar' => 'pic.jpg',
        'roles' => [],
        'enabled' => true
    ];
}
