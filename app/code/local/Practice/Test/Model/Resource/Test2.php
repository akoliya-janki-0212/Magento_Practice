<?php
class Practice_Test_Model_Resource_Test2 extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('practice_test/test2', 'test2_id');
    }
}
?>