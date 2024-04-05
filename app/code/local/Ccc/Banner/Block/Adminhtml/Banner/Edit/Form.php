<?php
class Ccc_Banner_Block_Adminhtml_Banner_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init form
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('banner_form');
        $this->setTitle(Mage::helper('banner')->__('Banner Information'));
        $this->setData('action', $this->getUrl('*/*/save'));
    }

    /**
     * Load Wysiwyg on demand and Prepare layout
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
    }

    protected function _prepareForm()
    {
        $model = Mage::registry('banner_block');

        $form = new Varien_Data_Form(
            array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post')
        );

        $form->setHtmlIdPrefix('banner_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('banner')->__('General Information'), 'class' => 'fieldset-wide'));

        if ($model->getBannerId()) {
            $fieldset->addField(
                'banner_id',
                'hidden',
                array(
                    'name' => 'banner id',
                )
            );
        }

        $fieldset->addField(
            'name',
            'text',
            array(
                'name' => 'banner_name',
                'label' => Mage::helper('banner')->__('Banner Name'),
                'title' => Mage::helper('banner')->__('Banner Name'),
                'required' => true,
            )
        );

        $fieldset->addField(
            'image',
            'file',
            array(
                'name' => 'banner_image',
                'label' => Mage::helper('banner')->__('Banner Image'),
                'title' => Mage::helper('banner')->__('Banner Image'),
                'required' => true,
            )
        );


        $fieldset->addField(
            'status',
            'select',
            array(
                'label' => Mage::helper('banner')->__('Status'),
                'title' => Mage::helper('banner')->__('Status'),
                'name' => 'is_active',
                'required' => true,
                'options' => array(
                    '1' => Mage::helper('banner')->__('Enabled'),
                    '0' => Mage::helper('banner')->__('Disabled'),
                ),
            )
        );
        if (!$model->getId()) {
            $model->setData('is_active', '1');
        }
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
?>