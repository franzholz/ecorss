<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

    call_user_func($emClass . '::addStaticFile', $_EXTKEY, './configurations', 'Ecodev: rss services');// ($extKey, $path, $title)

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile( TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Main/', 'Shop System');

});
