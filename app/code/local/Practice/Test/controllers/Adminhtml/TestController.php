<?php
class Practice_Test_Adminhtml_TestController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('practice_test/test');
        return $this;
    }
    public function indexAction()
    {
        $this->_title($this->__('Test'));
        $this->_initAction();
        $this->renderLayout();
    }
    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {

        $this->_title($this->__('Practice Test'))->_title($this->__('Practice Test'));

        $id = $this->getRequest()->getParam('test1_id');
        $model1 = Mage::getModel('practice_test/test1');
        $model2 = Mage::getModel('practice_test/test2');
        $model3 = Mage::getModel('practice_test/test3');

        if ($id) {
            $model1->load($id);
            if (!$model1->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('practice_test')->__('This banner no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($model1->getId() ? $model1->getTitle() : $this->__('New Test'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model1->setData($data);
        }

        Mage::register('practice_test1', $model1);

        $this->_initAction()
            ->_addBreadcrumb($id ? Mage::helper('practice_test')->__('Edit Test') : Mage::helper('practice_test')->__('Edit Test'), $id ? Mage::helper('practice_test')->__('Edit Test') : Mage::helper('practice_test')->__('Edit Test'));
        $this->renderLayout();

    }
}