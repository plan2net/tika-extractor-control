<?php

defined('TYPO3_MODE') or die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\Index\Indexer::class] = [
    'className' => \Plan2net\TikaExtractorControl\Resource\Index\Indexer::class
];
