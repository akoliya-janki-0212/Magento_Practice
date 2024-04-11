<?php
class Ccc_Banner_Block_Adminhtml_Banner_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'banner_id';
        $this->_controller = 'adminhtml_banner';
        $this->_blockGroup = 'banner';
        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('banner')->__('Save Banner'));
        $this->_updateButton('delete', 'label', Mage::helper('banner')->__('Delete Banner'));
        if (!Mage::getSingleton('admin/session')->isAllowed('ccc_banner/delete')) {
            $this->removeButton('delete');
        }

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('banner')->__('Save and Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);

        $this->_formScripts[] = "
        function toggleEditor() {
            if (tinyMCE.getInstanceById('block_content') == null) {
                tinyMCE.execCommand('mceAddControl', false, 'block_content');
            } else {
                tinyMCE.execCommand('mceRemoveControl', false, 'block_content');
            }
        }

        function saveAndContinueEdit(){
            editForm.submit($('edit_form').action+'back/edit/');
        }
    ";
    }
    /**
     * Get edit form container header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('banner_block')->getId()) {
            return Mage::helper('banner')->__("Edit Banner '%s'", $this->escapeHtml(Mage::registry('banner_block')->getTitle()));
        } else {
            return Mage::helper('banner')->__('New Banner');
        }
    }
}
?>