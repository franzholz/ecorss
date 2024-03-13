<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey, $table): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile( $extensionKey, 'Configuration/TypoScript', 'Ecodev: rss services');
}, 'ecorss', basename(__FILE__, '.php'));
