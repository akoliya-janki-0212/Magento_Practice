<?php
class Ccc_Banner_Block_Adminhtml_Banner_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _prepareCollection()
    {

        $collection = Mage::getModel('ccc_banner/banner')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        // Add columns for the grid
        $this->addColumn('banner_id', array(
            'header' => Mage::helper('banner')->__('Banner ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'banner_id',
        )
        );

        $this->addColumn('banner_img', array(
            'header' => Mage::helper('banner')->__('Banner Image'),
            'align' => 'left',
            'index' => 'banner_img',
        )
        );

        // Add more columns as needed

        return parent::_prepareColumns();
    }
}
