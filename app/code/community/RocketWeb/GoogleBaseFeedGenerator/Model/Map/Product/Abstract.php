<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2011 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract extends Varien_Object
{
	protected $_columns_map = null;
	protected $_empty_columns_replace_map = null;
	protected $skip = false;
	protected $_cache_gb_category = null;
	
	public function initialize()
    {
    	$this->setData('store_currency_code', Mage::app()->getStore($this->getData('store_code'))->getCurrentCurrencyCode());
    	
    	$this->setData('images_url_prefix', Mage::app()->getStore($this->getData('store_id'))->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA, false).'catalog/product');
    	$this->setData('images_path_prefix', Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath());

        return $this;
    }
    
    public function map()
    {
    	$this->_beforeMap();
    	$rows = $this->_map();
    	$this->_afterMap($rows);
    	return $rows;
    }
    
    public function _beforeMap() {
    	if (!$this->getConfigVar('submit_no_img'))
    	{
	    	// Don't add products without images.
	    	if (!$this->hasImage($this->getProduct()))
	    	{
	    		if ($this->getConfigVar('log_skip'))
	    		{
	    			$this->log(sprintf("product id %d product sku %s, skipped - product has 'Skip from Being Submitted' = 'Yes'.", $this->getProduct()->getId(), $this->getProduct()->getSku()));
	    		}
	    		$this->skip = true;
	    		return $this;
	    	}
    	}
    	return $this;
    }
    
    public function _afterMap($rows) {
    	return $this;
    }
	
	/**
    * Forms product's data row.
    * [column] => [value]
    * 
    * @return array
    */
    protected function _map()
    {
    	$fields = array();

    	foreach ($this->_columns_map as $column => $arr)
    	{
    		$fields[$column] = $this->mapColumn($column);
    	}
    	
    	$skip_column_empty = $this->getConfig()->getMultipleSelectVar('skip_column_empty', $this->getStoreId(), 'columns');
    	foreach ($skip_column_empty as $column)
    	{
    		if (isset($fields[$column]) && $fields[$column] == "")
    		{
    			if ($this->getConfigVar('log_skip'))
	    		{
	    			$this->log(sprintf("product id %d product sku %s, skipped - by product skip rule, has %s empty.", $this->getProduct()->getId(), $this->getProduct()->getSku(), $column));
	    		}
    			$this->skip = true;
    		}
    	}
    	
    	if (count($fields) != count($this->_columns_map))
    	{
    		if ($this->getConfigVar('log_skip'))
    		{
    			$this->log(sprintf("product id %d product sku %s, skipped - no enough data. It has data %s; should have for all columns %s.",
    				$this->getProduct()->getId(),
    				$this->getProduct()->getSku(),
    				is_array($fields) ? implode("~~", $fields) : "",
    				implode("~~", array_keys($this->_columns_map))));
    		}
    		$this->skip = true;
    	}
    	
    	return array($fields);
    }
    
    /**
     * @param string $column
     * @return string
     */
    protected function mapColumn($column)
    {
    	$value = "";
    	if (!isset($this->_columns_map[$column]))
    		return $value;
    	
    	$arr = $this->_columns_map[$column];
    	$args = array('map' => $arr);
    	
    	/* Column methods are required in a few cases.
    	   e.g. When child needs to get value from parent first. Further if value is empy takes value from it's own mapColumn* method.
    	   Can loop infinitely if misused.
    	*/
    	$method = 'mapColumn'.$this->_camelize($column);
    	if (method_exists($this, $method))
    	{
    		$value = $this->$method($args);
    	}
		else
		{
			$value = $this->getCellValue($args);
		}
		
		foreach ($this->_empty_columns_replace_map as $arr)
    	{
    		if ($column == $arr['column'])
    		{
	    		if ($value == "")
	    			continue;
	
	    		$args = array('map' => $arr);
				$method = 'mapAttribute'.$this->_camelize($arr['attribute']);
				if (method_exists($this, $method))
				{
					$value = $this->$method($args);
				}
				else
				{
					$value = $this->mapAttribute($args);
				}
    		}
    	}
    	
		return $value;
    }
    
    /**
     * Gets value either from directive method or attribute method.
     * @param array $args
     * @return mixed
     */
    public function getCellValue($args = array())
    {
    	$arr = $args['map'];
    	
    	if ($this->getConfig()->isDirective($arr['attribute'], $this->getStoreId()))
		{
			$method = 'mapDirective'.$this->_camelize(str_replace('rw_gbase_directive', '', $arr['attribute']));
			if (method_exists($this, $method))
    			$value = $this->$method($args);
    		else
    			$value = "";
		}
		else
		{
			$method = 'mapAttribute'.$this->_camelize($arr['attribute']);
			if (method_exists($this, $method))
			{
				$value = $this->$method($args);
			}
			else
			{
				$value = $this->mapAttribute($args);
			}
		}
    	
    	return $value;
    }
    
    /**
     * Process any other attribute.
     * @param array $params
     * @return string
     */
    protected function mapAttribute($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "")
    	{
			$cell = $default_value;
    		$cell = $this->cleanField($cell);
    		return $cell;
    	}
    	
    	$attribute = $this->getGenerator()->getAttribute($map['attribute']);
		if ($attribute === false)
			Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $map['attribute']));
		
		$cell = $this->getAttributeValue($product, $attribute);
    	
    	$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveId($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	$cell = $this->getProduct()->getId();
    	if ($this->getConfigVar('id_store_code', 'columns'))
    	{
    		if (trim($this->getConfigVar('id_add_store_unique', 'columns')) != "")
    		{
    			$cell .= trim(preg_replace('/[^a-zA-Z0-9]/', "", $this->getConfigVar('id_add_store_unique', 'columns')));
    		}
    		else
    		{
    			$cell .= preg_replace('/[^a-zA-Z0-9]/', "", $this->getStoreCode());
    		}
    	}

    	$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveUrl($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	$add_to_url = $this->getConfigVar('add_to_product_url', 'columns');
    	if (count($product->getCategoryIds()) > 0)
        {
        	/*$cell = sprintf('%s%s%s',
        		Mage::getStoreConfig('web/unsecure/base_url', $this->getData('store_id')),
        		$product->getUrlPath(),
        		$add_to_url);*/

        	$cell = sprintf('%s%s%s',
        		Mage::app()->getStore($this->getData('store_id'))->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK),
        		$product->getUrlPath(),
        		$add_to_url);
        }
        else
        {
        	// No category assigned to product => no url rewrite.
        	$cell = $product->getProductUrl().$add_to_url;
        }
        
    	return $cell;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveImageLink($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	$image = $product->getData('image');
        if ($image != 'no_selection' && $image != "")
            $cell = $this->getData('images_url_prefix') . $image;
        else
            $cell = '';
    	return $cell;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveAdditionalImageLink($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	if (($base_image = $product->getData('image')) != "")
    		$base_image = $this->getData('images_url_prefix') . $product->getData('image');
    	$urls = array();
    	$c = 0;
    	$media_gal_imgs = $product->getMediaGalleryImages();
    	if (is_array($media_gal_imgs) || $media_gal_imgs instanceof Varien_Data_Collection)
    	{
	    	foreach ($product->getMediaGalleryImages() as $galImage)
	    	{
	    		if (++$c > 10)
	    			break;
	    		// Skip base image.
	    		if (strcmp($base_image, $galImage->getUrl()) == 0)
	    			continue;
	    		$urls[] = $galImage->getUrl();
	    	}
    	}
    	$cell = implode(",", $urls);
    	return $cell;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectivePrice($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	$helper = Mage::helper('googlebasefeedgenerator/tax');
    	/* @var $helper Mage_Tax_Helper_Data */
    	
    	/* 0 - excluding tax
    	   1 - including tax */
    	$priceIncludesTax = ( $helper->priceIncludesTax($this->getStoreId()) ? true : false);
    	$includingTax = ($this->getConfigVar('add_tax_to_price', 'columns') ? true : false);
    	$price = $helper->getPrice($product, $this->getPrice(), $includingTax, false, false, null, $this->getStoreId(), $priceIncludesTax);
    	$cell = $price;

    	$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    protected function getPrice()
    {
    	return $this->getProduct()->getPrice();
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveSalePrice($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
		if (!$this->hasSpecialPrice())
			return $cell;
		
    	$helper = Mage::helper('googlebasefeedgenerator/tax');
    	/* @var $helper Mage_Tax_Helper_Data */
    	/* 0 - excluding tax
    	   1 - including tax */
    	$priceIncludesTax = ( $helper->priceIncludesTax($this->getStoreId()) ? true : false);
    	$includingTax = ($this->getConfigVar('add_tax_to_price', 'columns') ? true : false);
    	$price = $helper->getPrice($product, $this->getSpecialPrice(), $includingTax, false, false, null, $this->getStoreId(), $priceIncludesTax);
    	$cell = $price;

    	$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    protected function getSpecialPrice()
    {
    	return $this->getProduct()->getSpecialPrice();
    }
    
    protected function hasSpecialPrice()
    {
    	$has = false;
    	$product = $this->getProduct();
    	if ($this->getSpecialPrice() <= 0)
    		return $has;
    	if (is_empty_date($product->getSpecialFromDate()))
    		return $has;

    	$cDate = Mage::app()->getLocale()->date(null, null, Mage::app()->getLocale()->getDefaultLocale());
		$timezone = Mage::app()->getStore($this->getStoreId())->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
    	
		$fromDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
    	if ($timezone) $fromDate->setTimezone($timezone);
    	$fromDate->setDate(substr($product->getSpecialFromDate(), 0, 10), 'yyyy-MM-dd');
    	$fromDate->setTime(substr($product->getSpecialFromDate(), 11, 8), 'HH:mm:ss');
    	
    	$toDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
    	if (!is_empty_date($product->getSpecialToDate())) {
			if ($timezone) $toDate->setTimezone($timezone);
	    	$toDate->setDate(substr($product->getSpecialToDate(), 0, 10), 'yyyy-MM-dd');
	    	$toDate->setTime('23:59:59', 'HH:mm:ss');
		} else {
			if ($timezone) $toDate->setTimezone($timezone);
			$toDate->setDate($cDate->toString('yyyy-MM-dd'), 'yyyy-MM-dd');
			$toDate->setTime('23:59:59', 'HH:mm:ss');
			$toDate->add(7, Zend_Date::DAY);
		}

		if (($fromDate->compare($cDate) == -1 || $fromDate->compare($cDate) == 0) && ($toDate->compare($cDate) == 1 || $toDate->compare($cDate) == 0)) {
			$has = true;
		}
		return $has;
    }
    
    protected function mapDirectiveSalePriceEffectiveDate($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
		if (!$this->hasSpecialPrice())
			return $cell;

		$cDate = Mage::app()->getLocale()->date(null, null, Mage::app()->getLocale()->getDefaultLocale());
		$timezone = Mage::app()->getStore($this->getStoreId())->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
    	
		$fromDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
    	if ($timezone) $fromDate->setTimezone($timezone);
    	$fromDate->setDate(substr($product->getSpecialFromDate(), 0, 10), 'yyyy-MM-dd');
    	$fromDate->setTime(substr($product->getSpecialFromDate(), 11, 8), 'HH:mm:ss');
    	
    	$toDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
    	if (!is_empty_date($product->getSpecialToDate())) {
			if ($timezone) $toDate->setTimezone($timezone);
	    	$toDate->setDate(substr($product->getSpecialToDate(), 0, 10), 'yyyy-MM-dd');
	    	$toDate->setTime('23:59:59', 'HH:mm:ss');
		} else {
			if ($timezone) $toDate->setTimezone($timezone);
			$toDate->setDate($cDate->toString('yyyy-MM-dd'), 'yyyy-MM-dd');
			$toDate->setTime('23:59:59', 'HH:mm:ss');
			$toDate->add(7, Zend_Date::DAY);
		}
		
		$cell = $fromDate->toString(Zend_Date::ISO_8601)."/".$toDate->toString(Zend_Date::ISO_8601);
    	return $cell;
    }
    
    protected function mapDirectiveCurrency($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	$cell = $this->getData('store_currency_code');
    	
    	return $cell;
    }
    
    protected function mapDirectiveAvailability($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "")
    	{
    		$stock_status = $default_value;
    		$stock_status = trim(strtolower($stock_status));
    		
    		if (array_search($stock_status, $this->getConfig()->getAllowedStockStatuses()) === false)
				$stock_status = $this->getConfig()->getOutOfStockStatus();
    		
			$cell = $stock_status;
    		$cell = $this->cleanField($cell);
    		return $cell;
    	}
    	
    	if ($this->getConfigVar('use_default_stock', 'columns'))
    	{
    		$cell = $this->getConfig()->getOutOfStockStatus();
    		
    		$stockItem = Mage::getModel('cataloginventory/stock_item');
    		$stockItem->setStoreId($this->getStoreId());
    		$stockItem->getResource()->loadByProductId($stockItem, $product->getId());
    		$stockItem->setOrigData();
    		
    		if ($stockItem->getId() && $stockItem->getIsInStock())
    		{
				$cell = $this->getConfig()->getInStockStatus();
    		}
    	}
    	else
    	{
    		$stock_attribute = $this->getGenerator()->getAttribute($this->getConfigVar('stock_attribute_code', 'columns'));
    		if ($stock_attribute === false)
    			Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $this->getConfigVar('stock_attribute_code', 'columns')));
    		
    		$stock_status = trim(strtolower($this->getAttributeValue($product, $stock_attribute)));
    		if (array_search($stock_status, $this->getConfig()->getAllowedStockStatuses()) === false)
				$stock_status = $this->getConfig()->getOutOfStockStatus();
			
			$cell = $stock_status;
    	}
    	
    	return $cell;
    }
    
    protected function mapDirectiveExpirationDate($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	$cell = $this->getGenerator()->getData('expiration_date');

    	return $cell;
    }
    
    protected function mapDirectiveManufacturer($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "")
    	{
			$cell = $default_value;
    		$cell = $this->cleanField($cell);
    		return $cell;
    	}
    	
		$manufacturer_attribute = $this->getGenerator()->getAttribute($this->getConfigVar('manufacturer_attribute_code', 'columns'));
		if ($manufacturer_attribute === false)
			Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $this->getConfigVar('manufacturer_attribute_code', 'columns')));
		
		$brand = trim(strtolower($this->getAttributeValue($product, $manufacturer_attribute)));
		$cell = $brand;

		$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    protected function mapDirectiveCondition($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	
    	// only default value.
		$default_value = isset($map['default_value']) ? $map['default_value'] : "";
		$default_value = trim(strtolower($default_value));
		if (array_search($default_value, $this->getConfig()->getAllowedConditions()) === false)
		{
			$default_value = $this->getConfig()->getConditionNew();
		}
		$cell = $default_value;
		
		$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    protected function mapDirectivePaymentAccepted($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	
    	$payment_accepted = $this->getConfig()->getMultipleSelectVar('payment_accepted', $this->getStoreId(), 'columns');
    	$payment_accepted = implode(",", $payment_accepted);
    	$cell = $payment_accepted;
		
		$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapAttributeDescription($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	
    	$description_attribute = $this->getGenerator()->getAttribute($map['attribute']);
		if ($description_attribute === false)
			Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $map['attribute']));
		
		$description = $this->getAttributeValue($product, $description_attribute);
		$description = $this->cleanField($description);
		$max_len = (($max_len = $this->getConfigVar('max_description_length', 'columns')) > 10000 ? 10000 : $max_len );
		$remainder = "";
		if ($max_len > 0)
			$description = Mage::helper('core/string')->truncate($description, $max_len, '', $remainder, false);
		
		$cell = $description;
    	
    	$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapAttributeWeight($params = array())
    {
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "")
    	{
    		$weight = $default_value;
    		$weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
    		
			$cell = $weight;
    		$cell = $this->cleanField($cell);
    		return $cell;
    	}
    	
    	$weight_attribute = $this->getGenerator()->getAttribute($map['attribute']);
		if ($weight_attribute === false)
			Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $map['attribute']));
		
		$weight = $this->getAttributeValue($product, $weight_attribute);
		if ($weight != "")
			$weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
		
		$cell = $weight;
    	
    	$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    public function mapColumnGoogleProductCategory($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	$map_by_category = $this->getConfig()->getMapCategorySorted('google_product_category_by_category', $this->getStoreId());
    	$category_ids = $this->getProduct()->getCategoryIds();
    	if (!empty($category_ids) && count($map_by_category) > 0)
    	{
    		foreach ($map_by_category as $arr)
    		{
    			if (array_search($arr['category'], $category_ids) !== false)
    			{
    				$value = $arr['value'];
    				break;
    			}
    		}
    	}
    	if ($value != "")
    		return $value;
    	
    	$value = $this->getCellValue($args);
    	
		return $value;
	}
	
	public function mapColumnProductType($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	$map_by_category = $this->getConfig()->getMapCategorySorted('product_type_by_category', $this->getStoreId());
    	$category_ids = $this->getProduct()->getCategoryIds();
    	if (!empty($category_ids) && count($map_by_category) > 0)
    	{
    		foreach ($map_by_category as $arr)
    		{
    			if (array_search($arr['category'], $category_ids) !== false)
    			{
    				$value = $arr['value'];
    				break;
    			}
    		}
    	}
    	
    	if ($value != "")
    		return $value;
    	
    	$value = $this->getCellValue($args);
    	
		return $value;
	}
    
    /**
     * Cleans field by Google Base specs.
     *
     * @param string $field
     * @return string
     */
    protected function cleanField($field)
    {
        $field = strtr($field, array(
        	"\"" => " ",
        	"\t" => " ",
    		"\n" => " ",
    		"\r" => " ",
    	));

    	$field = strip_tags($field);
    	$field = preg_replace('/\s\s+/', ' ', $field);
        $field = str_replace(PHP_EOL, "", $field);
        $field = trim($field);
        
        return $field;
    }
    
    /**
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    protected function hasImage($product)
    {
    	$image = $product->getData('image');
        if ($image != 'no_selection' && $image != "")
        {
        	if (!file_exists($this->getData('images_path_prefix').$image))
        	{
            	return false;
        	}
        }
        else
        {
            return false;
        }
        return true;
    }
	
	/**
     * @param Mage_Catalog_Model_Product $product
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
     */
    public function checkSkipSubmission()
    {
    	// Don't submit to feed.
    	if ($this->getProduct()->getData('rw_google_base_skip_submi') == 1)
    		$this->skip = true;
    	return $this;
    }
    
    public function setApparelCategories()
    {
    	$this->setIsApparel(false);
    	$this->setIsApparelClothing(false);
    	$this->setIsApparelShoes(false);
    	if ($this->getConfigVar('is_turned_on', 'apparel') && isset($this->_columns_map['google_product_category']))
    	{
    		$gb_category = $this->mapColumn('google_product_category');
    		if ($this->matchApparelCategory($gb_category)) $this->setIsApparel(true);
    		if ($this->matchApparelClothingCategory($gb_category)) $this->setIsApparelClothing(true);
    		if ($this->matchApparelShoesCategory($gb_category)) $this->setIsApparelShoes(true);
    	}
    }
    
    /**
     * Test if apparel by product's google product category.
     * -1 not apparel
     * 0 can't determine - google_product_category is not set
     * 1 is apparel
     *
     * @param int $productId
     * @param int $parentId
     * @return bool
     */
    public function isApparelBySql($productId, $parentId = null, $category_ids = false)
    {
    	$is = -1;
    	if (!$this->getConfigVar('is_turned_on', 'apparel'))
    		return 0;
    	$column = 'google_product_category';
    	$map = $this->_columns_map;
    	if (!isset($map[$column]))
    		return 0;
    	
    	$map = $map[$column];
    	
    	$this->_cache_gb_category = "";
    	if (empty($this->_cache_gb_category))
    	{
	    	$map_by_category = $this->getConfig()->getMapCategorySorted('google_product_category_by_category', $this->getStoreId());
	    	if ($category_ids !== false && count($map_by_category) > 0)
	    	{
	    		foreach ($map_by_category as $value)
	    		{
	    			if (array_search($value['category'], $category_ids) !== false)
	    			{
	    				$this->_cache_gb_category = $value['value'];
	    				break;
	    			}
	    		}
	    	}
    	}
    	
    	if (isset($map['default_value']) && $map['default_value'] != "")
    	{
    		$this->_cache_gb_category = $map['default_value'];
    	}
    	
    	/* @todo try get google product category by product category ids - feature will be added. */
    	
    	if (empty($this->_cache_gb_category))
    	{
	    	$attribute_code = $map['attribute'];
	    	if ($this->getConfig()->isDirective($map['attribute'], $this->getStoreId()))
	    	{
	    		Mage::throwException("Unknown attribute code for column %s and directive %s", $column, $map['attribute']);
	    	}
	    	$attribute = $this->getGenerator()->getAttribute($attribute_code);
	    	$this->_cache_gb_category = $this->getTools()->getProductAttributeValueBySql($attribute, $attribute->getBackendType(), $productId, $this->getStoreId());
	    	if ($attribute->getSource() instanceof Mage_Eav_Model_Entity_Attribute_Source_Table && !is_null($this->_cache_gb_category))
	    		$this->_cache_gb_category = $this->getTools()->getProductAttributeSelectValue($attribute, $this->_cache_gb_category);
	    	if (!is_null($parentId) && empty($this->_cache_gb_category))
	    	{
	    		$this->_cache_gb_category = $this->getTools()->getProductAttributeValueBySql($attribute, $attribute->getBackendType(), $parentId, $this->getStoreId());
	    		if ($attribute->getSource() instanceof Mage_Eav_Model_Entity_Attribute_Source_Table && !is_null($this->_cache_gb_category))
	    			$this->_cache_gb_category = $this->getTools()->getProductAttributeSelectValue($attribute, $this->_cache_gb_category);
	    	}
    	}

    	if ($this->matchApparelCategory($this->_cache_gb_category))
    		$is = 1;
    	return $is;
    }
    
    /**
     * @param int $productId
     * @param int $parentId
     * @return bool
     */
    public function isClothingBySql($productId, $parentId = null)
    {
    	$is = -1;
    	if (is_null($this->_cache_gb_category))
    		if ($this->isApparelBySql($productId, $parentId) == 0)
    			return 0;
    	
    	if ($this->matchApparelCategory($this->_cache_gb_category) && $this->matchApparelClothingCategory($this->_cache_gb_category))
    		$is = 1;
    	
		return $is;
    }
    
    /**
     * @param int $productId
     * @param int $parentId
     * @return bool
     */
    public function isShoesBySql($productId, $parentId = null)
    {
    	$is = -1;
    	if (is_null($this->_cache_gb_category))
    		if ($this->isApparelBySql($productId, $parentId) == 0)
    			return 0;
    	
    	if ($this->matchApparelCategory($this->_cache_gb_category) && $this->matchApparelShoesCategory($this->_cache_gb_category))
    		$is = 1;
    	
		return $is;
    }
    
    public function matchApparelCategory($gb_category) { return $this->matchGoogleCategory($gb_category, $this->getConfigVar('google_product_category_apparel', 'apparel')); }
    public function matchApparelClothingCategory($gb_category) { return $this->matchGoogleCategory($gb_category, $this->getConfigVar('google_product_category_apparel_clothing', 'apparel')); }
    public function matchApparelShoesCategory($gb_category) { return $this->matchGoogleCategory($gb_category, $this->getConfigVar('google_product_category_apparel_shoe', 'apparel')); }
    
    protected function matchGoogleCategory($value, $g)
    {
    	$ret = false;
    	$g = preg_replace('/[^a-zA-Z0-9&]/', '', $g);
    	$value = preg_replace('/[^a-zA-Z0-9&]/', '', $value);
    	if (strpos($value, $g) !== false)
    		$ret = true;
    	return $ret;
    }
    
    public function getAttributeValue($product, $attribute)
    {
    	if ($attribute->getFrontendInput() == "select" || $attribute->getFrontendInput() == "multiselect")
			$value = $this->getAttributeSelectValue($product, $attribute);
		else
			$value = $product->getData($attribute->getAttributeCode());
		return $value;
    }
    
    /**
     * Gets option text value from product for attributes with frontend_type select.
     * Multiselect values are by default imploded with comma.
     * By default gets option text from admin store (recommended - english values in feed).
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return string
    */
    protected function getAttributeSelectValue($product, $attribute, $store_id = null)
    {
    	if (is_null($store_id))
    		$store_id = Mage_Core_Model_App::ADMIN_STORE_ID;
    	$pr = clone $product;
    	$attr = clone $attribute;
        $attr->setStoreId($store_id);
        $ret = $attr ? $attr->getFrontend()->getValue($pr) : '';
        $ret = (strcasecmp($ret, "No") == 0 ? '' : $ret);
        return $ret;
    }
	
	public function setColumnsMap($arr) { $this->_columns_map = $arr; return $this; }
	
	public function setEmptyColumnsReplaceMap($arr) { $this->_empty_columns_replace_map = $arr; return $this; }
				
	public function isSkip() { return $this->skip; }
	
	/**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Config
     */
    public function getConfig() { return $this->getGenerator()->getConfig(); }
    
    /**
     * @param string $key
     * @param string $section
     * @return mixed
     */
    public function getConfigVar($key, $section = 'settings') { return $this->getGenerator()->getConfigVar($key, $section); }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Tools
     */
    public function getTools() { return $this->getGenerator()->getTools(); }
    
    public function log($msg, $level = null) { return $this->getGenerator()->log($msg, $level); }
    
    public function genUnsetData()
    {
    	unset($this->_data);
    	unset($this->_origData);
    }
}