<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile( 'ecorss', 'Configuration/TypoScript', 'Ecodev: rss services');

});
