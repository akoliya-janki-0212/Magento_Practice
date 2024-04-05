<?php
class Ccc_Banner_Model_Resource_Banner extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('ccc_banner/banner', 'banner_id');
    }
}

?>