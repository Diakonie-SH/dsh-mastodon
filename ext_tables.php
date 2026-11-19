<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['dsh_mastodon']
    = \DiakonieSH\DshMastodon\Hooks\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['dsh_mastodon']
    = \DiakonieSH\DshMastodon\Hooks\DataHandlerHook::class;
