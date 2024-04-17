<?php
class Ccc_Report_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
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
            ->_setActiveMenu('ccc_report/report');
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {

        // var_dump($this->getLayout());die;
        $this->_title($this->__('Manage_Report'));
        $this->_initAction();
        $this->renderLayout();

        // $block = $this->getLayout()->createBlock('ccc_banner/banner');
        // echo '<pre>';
    }


}