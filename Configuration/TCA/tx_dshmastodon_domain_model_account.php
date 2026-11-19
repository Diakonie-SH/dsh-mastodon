<?php

return [
    'ctrl' => [
        'title' => 'Mastodon Account',
        'label' => 'domain',
        'iconfile' => 'EXT:dsh_mastodon/Resources/Public/Icons/Account.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'searchFields' => 'uid,domain',
        'rootLevel' => -1,
        'label_alt' => 'username',
        'label_userFunc' => \DiakonieSH\DshMastodon\Utility\TcaUtility::class . '->accountLabel',
    ],
    'types' => [
        0 => [
            'showitem' => <<<SHOWITEM
                domain,
                client_id,
                client_secret,
                username,
                redirect_url,
                auth_token,
                refresh_token,
                expires,
                owner,
                SHOWITEM,
        ],
    ],
    'columns' => [
        'client_id' => [
            'label' => 'Client ID',
            'description' => 'The OAuth app client key',
            'config' => [
                'type' => 'input',
                'size' => 80,
                'readOnly' => true,
            ],
        ],
        'client_secret' => [
            'label' => 'Client Secret',
            'description' => 'The OAuth app client secret',
            'config' => [
                'type' => 'password',
                'size' => 80,
                'readOnly' => true,
            ],
        ],
        'domain' => [
            'label' => 'Domain',
            'description' => 'Mastodon instance',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => true,
            ],
        ],
        'username' => [
            'label' => 'Username',
            'description' => 'User name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => true,
            ],
        ],
        'redirect_url' => [
            'label' => 'Redirect URL',
            'description' => 'Authorize redirect handler',
            'config' => [
                'type' => 'input',
                'size' => 80,
                'readOnly' => true,
            ],
        ],
        'auth_token' => [
            'label' => 'Authorization Token',
            'config' => [
                'type' => 'password',
                'readOnly' => true,
            ],
        ],
        'refresh_token' => [
            'label' => 'Refresh Token',
            'config' => [
                'type' => 'password',
                'readOnly' => true,
            ],
        ],
        'expires' => [
            'label' => 'Token Expiry',
            'config' => [
                'type' => 'number',
                'size' => 20,
                'readOnly' => true,
            ],
        ],
        'owner_id' => [
            'label' => 'Resource Owner ID',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => true,
            ],
        ],
        'owner' => [
            'label' => 'Resource Owner',
            'config' => [
                'type' => 'text',
                'readOnly' => true,
            ],
        ],
        'state' => [
            'label' => 'OAuth State',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => true,
            ],
        ],
    ],
];
