<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1776157090] = [
    'nodeName' => 'mastodonPost',
    'priority' => 10,
    'class' => \DiakonieSH\DshMastodon\Backend\Form\MastodonPostElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\DiakonieSH\DshMastodon\Scheduler\PublishToMastodonTask::class] = [
    'extension' => 'dsh_mastodon',
    'title' => 'Publish Mastodon Posts',
    'description' => 'Send posts which are past their publication date to Mastodon.',
];
