<?php
class Practice_Test_Block_Adminhtml_Test extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_test';
        $this->_blockGroup = 'practice_test';
        $this->_headerText = Mage::helper('practice_test')->__('Test');
        parent::__construct();

    }
}

?>