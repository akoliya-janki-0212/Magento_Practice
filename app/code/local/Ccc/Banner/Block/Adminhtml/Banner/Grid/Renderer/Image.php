<?php
class Ccc_Banner_Block_Adminhtml_Banner_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $imagePath = $row->getData($this->getColumn()->getIndex());
        if ($imagePath) {
            $mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $imageUrl = $mediaUrl . "banner/" . $imagePath;

            $html = '<img src="' . $imageUrl . '" width="100" height="100" />';
            return $html;
        } else {
            return '';
        }
    }
}
