<?php
class Practice_Test_Block_Adminhtml_Test_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'test1_id';
        $this->_controller = 'adminhtml_test';
        $this->_blockGroup = 'practice_test';
        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('practice_test')->__('Save Test'));
        $this->_updateButton('delete', 'label', Mage::helper('practice_test')->__('Delete Test'));

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('practice_test')->__('Save and Continue Edit'),
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
        var_dump(Mage::registry('practice_test1')->getId());
        die;
        if (Mage::registry('practice_test1')->getId()) {
            return Mage::helper('practice_test')->__("Edit Test '%s'", $this->escapeHtml(Mage::registry('practice_test1')->getTitle()));
        } else {
            return Mage::helper('practice_test')->__('New Test');
        }
    }
}
?>