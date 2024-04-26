<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Practice
 * @package     Practice_Test
 * @copyright  Copyright (c) 2006-2018 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * drop table 'practice_test/test1' if it exist
 */
$tableName = $installer->getTable('practice_test/test1');
if ($installer->getConnection()->isTableExists($tableName)) {
    // Table already exists, log a message and return
    Mage::log("Table $tableName already exists", null, 'practice_test.log');
    return;
}
/**
 * Create table 'practice_test/test1'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('practice_test/test1'))
    ->addColumn('test1_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Test1 Id')
    ->addColumn('test1_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
    ), 'Test1 Name')
    ->setComment('Practice Test1 Table');
$installer->getConnection()->createTable($table);

/**
 * drop table 'practice_test/test2' if it exist
 */
$tableName = $installer->getTable('practice_test/test2');
if ($installer->getConnection()->isTableExists($tableName)) {
    // Table already exists, log a message and return
    Mage::log("Table $tableName already exists", null, 'practice_test.log');
    return;
}
/**
 * Create table 'practice_test/test2'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('practice_test/test2'))
    ->addColumn('test2_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Test2 Id')
    ->addColumn('test2_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
    ), 'Test2 Name')
    ->setComment('Practice Test2 Table');
$installer->getConnection()->createTable($table);

/**
 * drop table 'practice_test/test3' if it exist
 */
$tableName = $installer->getTable('practice_test/test3');
if ($installer->getConnection()->isTableExists($tableName)) {
    // Table already exists, log a message and return
    Mage::log("Table $tableName already exists", null, 'practice_test.log');
    return;
}
/**
 * Create table 'practice_test/test3'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('practice_test/test3'))
    ->addColumn('test3_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Test3 Id')
    ->addColumn('test3_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
    ), 'Test3 Name')
    ->setComment('Practice Test3 Table');
$installer->getConnection()->createTable($table);
$installer->endSetup();
