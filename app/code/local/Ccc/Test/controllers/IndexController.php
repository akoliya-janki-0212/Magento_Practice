<?php
class Ccc_Test_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $varAbc = Mage::getModel('ccc_test/xyz');
        var_dump(get_class($varAbc));
    }
}
?>