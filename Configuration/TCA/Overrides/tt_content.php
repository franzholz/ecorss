<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey, $table): void {
    $languageSubpath = '/Resources/Private/Language/';

    $temporaryColumns = [
        'tx_ecorss_excludeFromFeed' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:excludeFromFeed',
            'config' => [
                'type' => 'check',
                "default" => "1",
            ]
        ],
    ];
    $columns = array_keys($temporaryColumns);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        $table,
        $temporaryColumns
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        implode(',', $columns),
        '',
        'after:hidden'
    );

    $listType = 'tx_ecorss_controllers_feed';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_content.list_type_pi1',
            $listType,
            'EXT:' . $extensionKey . '/Resources/Pubilc/Icons/Extension.gif'
        ],
        'list_type',
        $extensionKey
    );
}, 'ecorss', basename(__FILE__, '.php'));
