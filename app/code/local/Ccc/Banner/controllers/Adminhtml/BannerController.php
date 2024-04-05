<?php
class Ccc_Banner_Adminhtml_BannerController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Init actions
     *
     * @return Ccc_Banner_Adminhtml_BannerController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('banner/banner');
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_title($this->__('Manage_Banner'));
        $this->_initAction();
        $this->renderLayout();
    }

    /* Create new CMS page
     */
    public function newAction()
    {
        // the same form is used to create and edit
        // echo 12;
        $this->_forward('edit');
        // $this->loadLayout();
        // $this->_addContent($this->getLayout()->createBlock('ccc_banner/adminhtml_banner_edit'));
        // $this->renderLayout();
    }

    public function editAction()
    {

        $this->_title($this->__('banner'))->_title($this->__('Banner'));

        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('banner_id');
        $model = Mage::getModel('ccc_banner/banner');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('banner')->__('This banner no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($model->getId() ? $model->getTitle() : $this->__('New Banner'));

        // 3. Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {

            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        Mage::register('banner_block', $model);
        // 5. Build edit form
        $this->_initAction()
            ->_addBreadcrumb($id ? Mage::helper('banner')->__('Edit Banner') : Mage::helper('banner')->__('New Banner'), $id ? Mage::helper('banner')->__('Edit Banner') : Mage::helper('banner')->__('New Banner'))
            ->renderLayout();

        // $this->getLayout()->createBlock('banner/adminhtml_banner_edit')
        //     ->setData('action', $this->getUrl('*/*/save'));
        // print_r($this->getLayout()->getData('action'));
    }
    /**
     * Save action
     */
    public function saveAction()
    {
        // check if data sent
        if ($data = $this->getRequest()->getPost()) {

            $id = $this->getRequest()->getParam('banner_id');
            $model = Mage::getModel('ccc_banner/banner')->load($id);
            if (!$model->getId() && $id) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('banner')->__('This block no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }

            // init model and set data

            $model->setData($data);

            // try to save it
            try {
                // save the data
                $model->save();
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('banner')->__('The block has been saved.'));
                // clear previously saved data from session
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('banner_id' => $model->getId()));
                    return;
                }
                // go to grid
                $this->_redirect('*/*/');
                return;

            } catch (Exception $e) {
                // display error message
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                // save data in session
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                // redirect to edit form
                $this->_redirect('*/*/edit', array('banner_id' => $this->getRequest()->getParam('banner_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }


}
