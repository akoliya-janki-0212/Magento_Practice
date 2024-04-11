<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2011 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */


/**
 * Config data model
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Config extends Mage_Core_Model_Config_Data
{
	CONST XML_PATH_RGBF = 'rocketweb_googlebasefeedgenerator';
	
	protected $_product_attributes_codes = null;
	protected $_product_directives = null;
	protected $_cache_categories = null;
	
	/**
	 * Shortcut to Mage::getStoreConfig()
	 * 
     * @param string $key
     * @param mixed $store_id
     * @param string $section
     * @return mixed
     */
	public function getConfigVar($key, $store_id = null, $section = 'settings')
    {
    	switch ($key)
    	{
    		case "map_product_columns":
    			$ret = $this->getConfigVarMapProductColumns($key, $store_id, $section);
    			break;
    		default:
    			$ret = Mage::getStoreConfig(self::XML_PATH_RGBF.'/'.$section.'/'.$key, $store_id);
    	}
    	return $ret;
    }
    
    /**
     * Shortcut to config values with frontend type multiselect.
     * 
     * @param string $key
     * @param mixed $store_id
     * @param string $section
     * @return mixed
     */
	public function getConfigVarMapProductColumns($key, $store_id = null, $section = 'settings')
    {
    	$ret = Mage::getStoreConfig(self::XML_PATH_RGBF.'/'.$section.'/'.$key, $store_id);
    	if (empty($ret))
    	{
    		$ret = $this->convertDefaultMapProductColumns($store_id);
    	}
    	
    	// no order -> last
    	foreach ($ret as $k => $arr)
			if (!isset($arr['order']) || (isset($arr['order']) && $arr['order'] == ""))
			{
				$ret[$k]['order'] = 99999;
			}
    			
    	return $ret;
    }
    
    /**
     * Converts config var default_map_product_columns to format of backend type serialized array.
     *
     * @param int $store_id
     * @return array
     */
    public function convertDefaultMapProductColumns($store_id = null)
    {
    	$ret = array();
    	$default_map_product_columns = $this->getConfigVar('default_map_product_columns', $store_id, 'general');
    	foreach ($default_map_product_columns as $atrib => $arr)
    	{
    		$ret[] = array(
    			'column' => $arr['column'],
    			'attribute' => $atrib,
    			'default_value' => (isset($arr['default_value']) ? $arr['default_value'] : ''),
    			'order' => (isset($arr['order']) ? $arr['order'] : ''),
    		);
    	}
    	return $ret;
    }
    
    /**
     * @param string $key
     * @param mixed $store_id
     * @param string $section
     * @return array()
     */
    public function getMultipleSelectVar($key, $store_id = null, $section = 'settings')
    {
    	$str = $this->getConfigVar($key, $store_id, $section);
    	$arr = array();
    	if (!empty($str))
    		$arr = explode(",", $str);
    	$carr = $arr;
    	foreach ($carr as $k => $v)
    		if ($v === "")
    			unset($arr[$k]);
    	return $arr;
    }
    
    /**
     * Crates array of attributes and directives for dropdowns.
     * [code] => [label]
     * 
     * @param int $store_id
     * @param bool $with_directives
     * @return array
     */
    public function getProductAttributesCodes($store_id = null, $with_directives = true)
    {
    	$config_gbase = Mage::getSingleton('googlebasefeedgenerator/config');
    	$exclude_attributes = $config_gbase->getMultipleSelectVar('exclude_attributes', $store_id, 'general');
    	//$config_gbase = $config_gbase->serializeFields();
    	
    	if (is_null($this->_product_attributes_codes))
    	{
	    	$this->_product_attributes_codes = array();
	    	
	        $config = Mage::getModel('eav/config');
	    	$object = new Varien_Object(array('store_id' => $store_id));
	        $attributes_codes = $config->getEntityAttributeCodes('catalog_product', $object);
	        foreach($attributes_codes as $attribute_code)
	        {
	        	if (array_search($attribute_code, $exclude_attributes) !== false)
	        		continue;
	            $attribute = $config->getAttribute('catalog_product', $attribute_code);
				if ($attribute !== false && $attribute->getAttributeId() > 0)
				{
					$this->_product_attributes_codes[$attribute->getAttributeCode()] = addslashes($attribute->getFrontend()->getLabel().' ('.$attribute->getAttributeCode().')');
				}
	        }
	        asort($this->_product_attributes_codes);
    	}
    	
    	if ($with_directives === true && is_null($this->_product_directives))
        {
        	$this->_product_directives = array();
	        $default_map_product_columns = $config_gbase->getConfigVar('default_map_product_columns', $store_id, 'general');
	        $directives = $config_gbase->getConfigVar('directives', $store_id, 'general');
	        
	        // Add first directives:
	    	/*foreach ($default_map_product_columns as $code => $arr)
	    	{
	    		if (isset($directives[$code]))
	    		{
	    			$this->_product_directives[$code] = $directives[$code]['label'];
	    		}
	    	}*/
	    	foreach ($directives as $code => $arr)
	    	{
	    		$this->_product_directives[$code] = $arr['label'];
	    	}
	    	asort($this->_product_directives);
        }
        
        $return = array();
        if ($with_directives === true)
        {
        	$return = array_merge($this->_product_directives, $this->_product_attributes_codes);
        }
        else
        {
        	$return = $this->_product_attributes_codes;
        }
    	
    	return $return;
    }
    
    /**
     * @param string $name
     * @param int $store_id
     * @return array
     */
    public function getMapCategorySorted($name, $store_id = null)
    {
    	$map = $this->getConfigVar($name, $store_id, 'columns');
    	$ret = array();
    	if (empty($map))
    		return array();
    	
    	$order = array(); $tt = array();
    	foreach ($map as $k => $value)
    	{
    		if (isset($value['value']) && trim($value['value']) != "")
    		{
	    		if (isset($value['order']) && $value['order'] != "")
	    		{
	    			$order[$k] = $value['order'];
	    		}
	    		else
	    		{
	    			$tt[$k] = "";
	    		}
    		}
    	}
    	asort($order);
    	
    	foreach ($order as $k => $v)
    	{
    		$ret[$k] = $map[$k];
    		if (isset($ret[$k]['value']))
    			$ret[$k]['value'] = trim($ret[$k]['value']);
    	}
    	foreach ($tt as $k => $v)
    		$ret[$k] = $map[$k];
    	
    	return $ret;
    }
    
    /**
     * @param string $code
     * @return bool $code
     */
    public function isDirective($code, $store_id = null)
    {
    	$directives = $this->getConfigVar('directives', $store_id, 'general');
    	if (isset($directives[$code]))
    		return true;
    	return false;
    }
    
    public function getAllowedStockStatuses()
    {
    	return array('in stock', 'out of stock', 'available for order', 'preorder');
    }
    public function getInStockStatus() { return 'in stock'; }
    public function getOutOfStockStatus() { return 'out of stock'; }
    
    public function getAllowedConditions()
    {
    	return array('new', 'used', 'refurbished');
    }
    public function getConditionNew() { return 'new'; }
    
    public function getAllowedGender()
    {
    	return array('female', 'male', 'unisex');
    }
    
    public function getAllowedAgeGroup()
    {
    	return array('adult', 'kids');
    }
    
    public function parseEmailsTo($str)
    {
    	if (empty($str))
			return array();
		$str = str_replace("\r", " ", $str);
		$emails = preg_split("/[\s,]+/", $str);
		
		return $emails;
    }
    
    public function getAllCategories()
    {
    	if (is_null($this->_cache_categories))
    	{
	    	$this->_cache_categories = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('name')
				->addAttributeToSort('path', 'asc')
				->load()
				->toArray();
    	}
		return $this->_cache_categories;
    }
}