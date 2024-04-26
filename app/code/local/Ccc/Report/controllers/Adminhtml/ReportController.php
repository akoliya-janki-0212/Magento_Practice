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


    // Call the function to set default=true for specified columns

    public function updateAction()
    {
        $checkedColumns = $this->getRequest()->getPost('checkedData');
        $reportHelper = Mage::helper('report/report');
        $XMlReadedData = $reportHelper->getFieldsetData('ccc_report_columns');
        $reportModel = Mage::getModel('ccc_report/report');
        $reportModel->setTempColumns($checkedColumns);
        $responseData = $reportHelper->getJsonData(
            $reportModel->getConnection(
                $reportModel->prepareQuery($XMlReadedData)
            )
        );

        // Return response as JSON
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody($responseData);
    }

}