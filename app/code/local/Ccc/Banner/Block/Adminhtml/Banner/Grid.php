<?php
class Ccc_Banner_Block_Adminhtml_Banner_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _prepareCollection()
    {
        // Load your collection
        $collection = Mage::getModel('ccc_banner/banner')->getCollection();
        if (!Mage::getSingleton('admin/session')->isAllowed('ccc_banner/rows')) {
            // Modify the SQL query to apply the order by
            $collection->setOrder('banner_id', 'DESC');
            // Apply custom limit
            $customLimit = 1; // Change this to your desired limit
            $collection->getSelect()->limit($customLimit);
            $this->setCollection($collection);
            return $this;
        }
        $this->setCollection($collection);
        // Set the collection to the grid
        return parent::_prepareCollection();
    }
    public function checkColumn($column)
    {
        return Mage::getSingleton('admin/session')->isAllowed('ccc_banner/columns/' . $column);
    }
    protected function _prepareColumns()
    {
        $columns = array(
            'banner_id' => array(
                'header' => Mage::helper('banner')->__('Banner Id'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'banner_id',
                'is_allowed' => $this->checkColumn('id'), // ACL check
            ),
            'banner_name' => array(
                'header' => Mage::helper('banner')->__('Banner Name'),
                'align' => 'left',
                'index' => 'banner_name',
                'is_allowed' => $this->checkColumn('name'), // ACL check
            ),
            'banner_image' => array(
                'header' => Mage::helper('banner')->__('Banner Image'),
                'align' => 'center',
                'index' => 'banner_image',
                'renderer' => 'Ccc_Banner_Block_Adminhtml_Banner_Grid_Renderer_Image', // Use custom renderer for image column
                'is_allowed' => $this->checkColumn('image'), // ACL check

            ),
            'status' => array(
                'header' => Mage::helper('banner')->__('Status'),
                'align' => 'left',
                'index' => 'status',
                'is_allowed' => $this->checkColumn('status'), // ACL check

            ),
            'show_on' => array(
                'header' => Mage::helper('banner')->__('Show On'),
                'align' => 'left',
                'index' => 'show_on',
                'is_allowed' => $this->checkColumn('showon'), // ACL check

            ),
        );
        // Loop through each column
        foreach ($columns as $columnKey => $columnConfig) {
            // Add column
            if ($columnConfig['is_allowed'] == true) {
                $this->addColumn($columnKey, $columnConfig);
            }
        }
        // Add more columns as needed

        return parent::_prepareColumns();
    }
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('banner_id' => $row->getId()));
    }
    // MAss Actions 
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('banner_id');
        $this->getMassactionBlock()->setFormFieldName('banner_id'); // Change to 'banner_id'

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label' => Mage::helper('banner')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('banner')->__('Are you sure you want to delete selected banners?')
            )
        );

        $statuses = Mage::getSingleton('ccc_banner/status')->getOptionArray();

        array_unshift($statuses, array('label' => '', 'value' => ''));
        $this->getMassactionBlock()->addItem(
            'status',
            array(
                'label' => Mage::helper('banner')->__('Change status'),
                'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
                'additional' => array(
                    'visibility' => array(
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => Mage::helper('banner')->__('Status'),
                        'values' => $statuses
                    )
                )
            )
        );

        Mage::dispatchEvent('banner_adminhtml_banner_grid_prepare_massaction', array('block' => $this));
        return $this;
    }



}
