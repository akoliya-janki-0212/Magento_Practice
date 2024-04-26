<?php
class Practice_Test_Model_Resource_Test1 extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('practice_test/test1', 'test1_id');
    }
}
?>