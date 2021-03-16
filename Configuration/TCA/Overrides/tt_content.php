<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_content';

    $temporaryColumns = [
        'tx_ecorss_excludeFromFeed' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ecorss/locallang_db.xml:excludeFromFeed',
            'config' => [
                'type' => 'check',
                "default" => "1",
            ]
        ],
    ];
    $columns = array_keys($temporaryColumns);
    
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $temporaryColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        implode(',', $columns),
        '', 
        'after:hidden'
    );
    
    $listType = 'tx_ecorss_controllers_feed';
    $extensionKey = 'ecorss';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';
    
    call_user_func($emClass . '::addPlugin', array('LLL:EXT:ecorss/locallang_db.xml:tt_content.list_type_pi1', 'tx_ecorss_controllers_feed'));

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . $extensionKey . '/locallang_db.xml:tt_content.list_type_pi1',
            $listType,
            'EXT:' . $extensionKey . '/ext_icon.gif'
        ],
        'list_type',
        $extensionKey
    );
});

