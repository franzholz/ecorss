<?php
$extensionPath = t3lib_extMgm::extPath('ecorss');
return array(
	'tx_ecorss_controllers_feed' => $extensionPath . 'controllers/tx_ecorss_controllers_feed.php',
	'tx_ecorss_models_feed' => $extensionPath . 'models/class.tx_ecorss_models_feed.php',
	'tx_ecorss_views_feed' => $extensionPath . 'views/class.tx_ecorss_views_feed.php',
);
?>