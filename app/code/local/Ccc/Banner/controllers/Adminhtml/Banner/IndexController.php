<?php
class Ccc_Banner_Adminhtml_Banner_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        echo 234;
        $this->loadLayout();
        $this->_title($this->__("Manage Banners"));
        $this->renderLayout();
    }
}
