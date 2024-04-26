<?php
class Practice_Test_Block_Adminhtml_Test_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);

    }
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('practice_test/test1')->getCollection()
            ->addFieldToSelect('test1_name');
        $collection->getSelect()->join(
            array('test2' => 'test2'),
            'main_table.test1_id = test2.test2_id',
            array('test2_name' => 'test2_name')
        );
        $collection->getSelect()->join(
            array('test3' => 'test3'),
            'main_table.test1_id = test3.test3_id',
            array('test3_name' => 'test3_name')
        );
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'test1_name',
            array(
                'header' => Mage::helper('practice_test')->__('Test1 Name'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'test1_name',
            )
        );
        $this->addColumn(
            'test2_name',
            array(
                'header' => Mage::helper('practice_test')->__('Test2 Name'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'test2_name',
            )
        );
        $this->addColumn(
            'test3_name',
            array(
                'header' => Mage::helper('practice_test')->__('Test3 Name'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'test3_name',
            )
        );
        return parent::_prepareColumns();
    }
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('test1_id' => $row->getId()));
    }
}
