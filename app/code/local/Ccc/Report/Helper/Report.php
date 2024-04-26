<?php
class Ccc_Report_Helper_Report extends Mage_Core_Helper_Abstract
{
    protected $_labels = [];
    protected $_default = [];
    public function getFieldsetData($root)
    {
        $rootNode = Mage::getConfig()->getNode($root);
        if (!$rootNode) {
            return null;
        }
        return $rootNode ? $rootNode->children() : null;
    }
    public function getJsonData($result)
    {
        return json_encode($result);
    }
    public function setLabel($column, $label)
    {
        $this->_labels[$column] = $label;
        return $this;
    }
    public function getLabel()
    {
        return $this->_labels;
    }
    public function setDefault($default)
    {
        $this->_default[] = $default;
        return $this;
    }
    public function getDefault()
    {
        return $this->_default;
    }

}
?>