<?php

require_once dirname(__FILE__) . '/../../../../../../app/Mage.php';

set_time_limit(0);
/* Setting memory limit depends on the number of products exported.*/
/*ini_set('memory_limit','600M');*/
error_reporting(E_ALL);

if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}

// Only for urls
// Don't remove this
$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app('admin')->setUseSessionInUrl(false);

// If required, replace Mage_Core_Model_Store::DEFAULT_CODE with your 'storecode'.
$Generator = Mage::getSingleton('googlebasefeedgenerator/generator', array(
	'store_code'   => Mage_Core_Model_Store::DEFAULT_CODE,
	'batch_mode'    => true,
    'schedule_id' => uniqid(rand(), true),
    'mage_cron'    => false,
	));
$Generator->run();