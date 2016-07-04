<?php
$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$key = 'ecorss';

$extensionPath = call_user_func($emClass . '::extPath', $key, $script);


return array(
	'tx_ecorss_controllers_feed' => $extensionPath . 'controllers/tx_ecorss_controllers_feed.php',
	'tx_ecorss_models_feed' => $extensionPath . 'models/class.tx_ecorss_models_feed.php',
	'tx_ecorss_views_feed' => $extensionPath . 'views/class.tx_ecorss_views_feed.php',
);
?>