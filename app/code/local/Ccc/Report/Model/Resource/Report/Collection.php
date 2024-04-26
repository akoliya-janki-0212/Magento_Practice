<?php
class Ccc_Report_Model_Resource_Report_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('ccc_report/report');
    }
}
?>