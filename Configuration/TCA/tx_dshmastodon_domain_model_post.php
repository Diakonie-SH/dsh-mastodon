<?php

return [
    'ctrl' => [
        'title' => 'Mastodon Post',
        'label' => 'bodytext',
        'iconfile' => 'EXT:dsh_mastodon/Resources/Public/Icons/Post.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'searchFields' => 'uid,bodytext',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],
    'types' => [
        0 => [
            'showitem' => <<<SHOWITEM
                --palette--;;meta,
                bodytext,
                images,
                --palette--;;published,
                SHOWITEM,
        ],
    ],
    'palettes' => [
        'meta' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:meta',
            'showitem' => 'account, visibility',
        ],
        'published' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:publication',
            'showitem' => 'publish_at, hidden, --linebreak--, url,',
        ],
    ],
    'columns' => [
        'account' => [
            'label' => 'Account',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dshmastodon_domain_model_account',
                'required' => true,
            ],
        ],
        'visibility' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:visibility',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:visibility.public',
                        'value' => 0,
                        'icon' => 'EXT:dsh_mastodon/Resources/Public/Icons/visibility-public.svg',
                    ],
                    [
                        'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:visibility.unlisted',
                        'value' => 1,
                        'icon' => 'EXT:dsh_mastodon/Resources/Public/Icons/visibility-unlisted.svg',
                    ],
                    [
                        'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:visibility.private',
                        'value' => 2,
                        'icon' => 'EXT:dsh_mastodon/Resources/Public/Icons/visibility-private.svg',
                    ],
                    [
                        'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:visibility.direct',
                        'value' => 3,
                        'icon' => 'EXT:dsh_mastodon/Resources/Public/Icons/visibility-direct.svg',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'bodytext' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:text',
            'config' => [
                'type' => 'text',
                'enableRichtext' => false,
            ],
        ],
        'publish_at' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:publish_at',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'published' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:published',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        'label' => 'Yes',
                        'value' => 1,
                    ],
                ],
                'readOnly' => true,
            ],
        ],
        'unpublished' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:unpublished',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        'label' => 'Yes',
                        'value' => 1,
                    ],
                ],
                'readOnly' => true,
            ],
        ],
        'url' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:url',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['url'],
                'size' => 80,
                'readOnly' => true,
            ],
        ],
        'tablename' => [
            'label' => 'Referenced Table',
            'config' => [
                'type' => 'input',
                'size' => 80,
                'readOnly' => 1,
            ],
        ],
        'uid_foreign' => [
            'label' => 'Referenced Record',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'readOnly' => 1,
            ],
        ],
        'images' => [
            'label' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang.xlf:images',
            'config' => [
                'type' => 'file',
                'maxitems' => 4,
                'allowed' => 'common-image-types',
            ],
        ],

        // @todo none field for rendering link to referenced record
        // @todo language, content warning
    ],
];
