<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');
//update the extension plugin form in the backend


$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$divClass = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

if (
	class_exists($divClass)
) {
	// nothing
} else {
	$divClass = 't3lib_div';
}

$tempColumns = Array (
	'tx_ecorss_excludeFromFeed' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:ecorss/locallang_db.xml:excludeFromFeed',
		'config' => Array (
			'type' => 'check',
			"default" => "1",
		)
	),
);

if (
	version_compare(TYPO3_version, '6.1.0', '<')
) {
	call_user_func($divClass . '::loadTCA', 'tt_content');
}

call_user_func($emClass . '::addTCAcolumns', 'tt_content', $tempColumns, 1);
call_user_func($emClass . '::addToAllTCAtypes', 'tt_content', 'tx_ecorss_excludeFromFeed', '', 'after:hidden');

call_user_func($emClass . '::addStaticFile', $_EXTKEY, './configurations', 'Ecodev: rss services');// ($extKey, $path, $title)
call_user_func($emClass . '::addPlugin', array('LLL:EXT:ecorss/locallang_db.xml:tt_content.list_type_pi1', 'tx_ecorss_controllers_feed'));

?>