<?php

if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    return;
}

$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['tx_dshmastodon_post'] = [
    'label' => 'Mastodon Post',
    'config' => [
        'type' => 'none',
        'renderType' => 'mastodonPost',
    ],
];

$GLOBALS['TCA']['tx_news_domain_model_news']['palettes']['mastodon'] = [
    'label' => 'Mastodon-Post',
    'showitem' => 'tx_dshmastodon_post,',
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_news_domain_model_news', '--palette--;;mastodon');
