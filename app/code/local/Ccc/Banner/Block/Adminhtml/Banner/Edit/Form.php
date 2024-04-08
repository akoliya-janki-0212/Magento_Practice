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
        $this->setData('banner_form', $this->getUrl('*/*/save'));
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
        $isEdit = ($model && $model->getId());

        $form = new Varien_Data_Form(
            array('id' => 'edit_form', 'action' => $this->getUrl('*/*/save'), 'method' => 'post', 'enctype' => 'multipart/form-data')
        );

        $form->setHtmlIdPrefix('block_');


        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('banner')->__('General Information'), 'class' => 'fieldset-wide'));

        if ($isEdit && $model->getBannerId()) {
            $fieldset->addField(
                'banner_id',
                'hidden',
                array(
                    'name' => 'banner_id',
                )
            );
        }


        $fieldset->addField(
            'banner_name',
            'text',
            array(
                'name' => 'banner_name',
                'label' => Mage::helper('banner')->__('Banner Name'),
                'title' => Mage::helper('banner')->__('Banner Name'),
                'required' => true,
            )
        );

        $fieldset->addField(
            'banner_image',
            'file',
            array(
                'name' => 'banner_image',
                'label' => Mage::helper('banner')->__('Banner image'),
                'title' => Mage::helper('banner')->__('Banner image'),
                // Remove 'required' attribute only in edit mode
                'required' => !$isEdit,
            )
        );

        // Add current image display if in edit mode
        if ($isEdit) {
            $currentImageUrl = $model->getBannerImage();
            if ($currentImageUrl) {
                $fieldset->addField(
                    'current_image',
                    'note',
                    array(
                        'label' => Mage::helper('banner')->__('Current Image'),
                        'text' => '<img src="' . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'banner/' . $currentImageUrl . '" height="200" />'

                    )
                );
            }
        }

        $fieldset->addField(
            'status',
            'select',
            array(
                'label' => Mage::helper('banner')->__('Status'),
                'title' => Mage::helper('banner')->__('Status'),
                'name' => 'status',
                'required' => true,
                'options' => array(
                    '1' => Mage::helper('banner')->__('Enabled'),
                    '0' => Mage::helper('banner')->__('Disabled'),
                ),
            )
        );
        if (!($model->getId())) {
            $model->setData('status', '1');
        }

        $fieldset->addField(
            'show_on',
            'select',
            array(
                'label' => Mage::helper('banner')->__('Show ON'),
                'title' => Mage::helper('banner')->__('Show ON'),
                'name' => 'show_on',
                'required' => true,
                'options' => array(
                    '1' => Mage::helper('banner')->__('1'),
                    '0' => Mage::helper('banner')->__('0'),
                ),
            )
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
?>