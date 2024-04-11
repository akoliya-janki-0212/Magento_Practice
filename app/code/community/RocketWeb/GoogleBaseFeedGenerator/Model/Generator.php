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

class RocketWeb_GoogleBaseFeedGenerator_Model_Generator extends Varien_Object
{
	CONST PRODUCT_TYPE_ASSOC = 'simple_associated';
	
	protected $_handle = null;
	
	protected $_count_products_exported = 0;
	protected $_count_products_skipped = 0;
	
	protected $_alternate_feed_dir = null;
	
	protected $_columns_map = null;
	protected $_empty_columns_replace_map = null;
	
	protected $_collection = null;
	
	protected $_total_items = null;
	protected $_current_iter = 0;
	
	protected $_currencyObject;
	protected $_currencyRate;
	
	/**
	 * @var RocketWeb_GoogleBaseFeedGenerator_Model_Batch
	 */
	protected $batch;
	
	/**
	 * All catalog_product eav attributes used.
	 *
	 * @var array of Mage_Catalog_Model_Resource_Eav_Attribute
	 */
	protected $_attributes = array();

    protected function _construct()
    {
    	parent::_construct();
    	
    	if (!$this->hasData('store_code'))
    		$this->setData('store_code', Mage_Core_Model_Store::DEFAULT_CODE);
    	try
    	{
    		Mage::app()->getStore($this->getData('store_code'));
    	}
    	catch (Exception $e)
    	{
    		Mage::throwException(sprintf('Store with code \'%s\' doesn\'t exists.', $this->getData('store_code')));
    	}
    	$this->setData('store_id', Mage::app()->getStore($this->getData('store_code'))->getStoreId());
    	$this->setData('website_id', Mage::app()->getStore($this->getData('store_code'))->getWebsiteId());
    }
    
    protected function initialize()
    {
    	$this->initSavePath();
    	$this->getColumnsMap();
    	$this->getEmptyColumnsReplaceMap();
    	
        $this->setData('expiration_date', date('Y-m-d', Mage::getModel('core/date')->timestamp(time()) + 3600 * 24 * (int) $this->getConfigVar('ttl', 'columns')));
        return $this;
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Batch
     */
    public function getBatch()
    {
    	if ($this->getBatchMode() && is_null($this->batch))
    	{
    		if (!$this->getScheduleId())
    			Mage::throwException(sprintf('Invalid schedule_id %s', $this->getScheduleId()));
    		$this->batch = Mage::getModel('googlebasefeedgenerator/batch', array(
	    		'store_code' => $this->getStoreCode(),
	    		'store_id'	 => $this->getStoreId(),
	    		'website_id' => $this->getWebsiteId(),
	    		'config'	 => $this->getConfig(),
	    		'generator'	 => $this,
	    		'schedule_id' => $this->getScheduleId(),
	    	));
    	}
    	
    	return $this->batch;
    }

    public function run()
    {
    	if (!$this->getConfigVar('is_turned_on'))
    		return;

    	if ($this->getBatchMode() && !$this->getConfigVar('use_batch_segmentation'))
    		$this->setBatchMode(false);
    	
    	$this->initialize();
    	
    	$this->_total_items = null;
    	if($this->getBatchMode())
    	{
    		$count_coll = clone $this->_getCollection();
    		$this->_total_items = $count_coll->getSize();
    		$this->getBatch()->setTotalItems($this->_total_items);
    		unset($count_coll);
    		$batch_limit = ($this->getConfigVar('batch_limit') == 0 ? 1000 : $this->getConfigVar('batch_limit'));
    		$batch_limit = ($batch_limit <= $this->_total_items ? $batch_limit : $this->_total_items);
    		$this->getBatch()->setLimit($batch_limit);
    		// Can't get lock, another script is running.
    		if (!$this->getBatch()->aquireLock())
    		{
    			return $this;
    		}
    	}
    	
    	$collection = $this->getCollection();
    	if (is_null($this->_total_items))
    		$this->_total_items = $collection->getSize();
    	if(!$this->getBatchMode() || ($this->getBatchMode() && $this->getBatch()->getIsNew()))
    	{
    		$this->writeFeed($this->getHeader(), false);
    	}
    	
    	$product_types = $this->getConfig()->getMultipleSelectVar('product_types', $this->getData('store_id'));
    	$this->_current_iter = 0;
    	
    	Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this, 'processProductCallback')), array(
    		'product_types' => $product_types,
    		
    	));

    	$this->closeHandle();
    	$this->log(sprintf('products (added, skipped)(%3d, %3d) to file %s', $this->getCountProductsExported(), $this->getCountProductsSkipped(), $this->getFeedPath()));
    	
    	if($this->getBatchMode())
    	{
    		$this->getBatch()->releaseLock();
    	}
    	
    	return $this;
    }
    
    /**
     * @param int $type_id
     * @param array $args
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
     */
    protected function getProductMapModel($type_id, $args = array())
    {
    	$params = array(
    		'store_code' => $this->getData('store_code'),
    		'store_id' => $this->getData('store_id'),
    		'website_id' => $this->getData('website_id'),
    	);
		
    	$is_assoc = isset($args['is_assoc']) && $args['is_assoc'] ? true : false;
    	$is_apparel = isset($args['is_apparel']) && $args['is_apparel'] ? true : false;
    	/*$is_clothing = isset($args['is_clothing']) && $args['is_clothing'] ? true : false;
    	$is_shoes = isset($args['is_shoes']) && $args['is_shoes'] ? true : false;*/
		
    	switch ($type_id)
    	{
    		case 'abstract':
    			$productMap = Mage::getModel('googlebasefeedgenerator/map_product_abstract', $params);
    			break;
    		
    		case 'simple':
    			if ($is_assoc && $is_apparel)
    				Mage::throwException(sprintf('Product type %s is not allowed here.', $type_id));
    			if ($is_assoc)
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_associated', $params);
    			else
    				if ($is_apparel)
    					$productMap = Mage::getModel('googlebasefeedgenerator/map_product_simple_apparel', $params);
    				else
    					$productMap = Mage::getModel('googlebasefeedgenerator/map_product_simple', $params);
    			break;
    		
    		case 'configurable':
    			if ($is_apparel)
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_configurable_apparel', $params);
    			else
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_configurable', $params);
    			break;
    		case 'bundle':
    			if ($is_apparel)
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_bundle_apparel', $params);
    			else
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_bundle', $params);
    			break;
    		
    		default:
    			$productMap = Mage::getModel('googlebasefeedgenerator/map_product_'.$type_id, $params);
		    	if ($productMap === false)
		    		Mage::throwException(sprintf('Product type \'%s\' isn\'t allowed.', $type_id));
    			
    	}

    	return $productMap;
    }
    
    public function processProductCallback($args)
    {
    	$product_types = $args['product_types'];
    	$row = $args['row'];
    	$parentEntityId = null;
    	
    	if (++$this->_current_iter % $this->getLogCountStep($this->_total_items) == 0)
        		$this->log(sprintf("(%3d, %3d) products (processed, max)", $this->_current_iter, $this->_total_items));
        
        if (($category_ids = $this->getTools()->getCategoriesById($row['entity_id'])) !== false)
        {
        	if (count(array_intersect($this->getConfig()->getMultipleSelectVar('skip_category', $this->getStoreId(), 'columns'), $category_ids)) > 0)
        	{
        		if ($this->getConfigVar('log_skip'))
	    		{
	    			$this->log(sprintf("product id %d product sku %s, skipped - by category.", $row['entity_id'], $row['sku']));
	    		}
	    		$this->_count_products_skipped++;
	    		return;
        	}
        }
        
    	$is_assoc = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
    	if ($is_assoc !== false)
    		$parentEntityId = $is_assoc['parent_entity_id'];
    	
    	// Skip associated products if not allowed.
    	if (array_search(self::PRODUCT_TYPE_ASSOC, $product_types) === false && $is_assoc !== false)
    	{
    		return;
    	}
    	
    	$pMap = $this->getProductMapModel('abstract')
    		->setGenerator($this)
			->setColumnsMap($this->getColumnsMap())
			->setEmptyColumnsReplaceMap($this->getEmptyColumnsReplaceMap())
			->initialize();
    	$is_apparel = $pMap->isApparelBySql($row['entity_id'], $parentEntityId, $category_ids);
    	
    	// Skip apparels if disabled.
    	if ($is_apparel && !$this->getConfigVar('is_turned_on', 'apparel'))
    	{
    		if ($this->getConfigVar('log_skip'))
    		{
    			$this->log(sprintf("product id %d product sku %s, skipped - apparel products are disabled.", $row['entity_id'], $row['sku']));
    		}
    		$this->_count_products_skipped++;
    		return;
    	}
    	
    	// Skip associated products with google catagory Apparel & Accessories
    	if (array_search(self::PRODUCT_TYPE_ASSOC, $product_types) !== false && $is_assoc !== false)
    	{
    		if ($is_apparel == 1)
    		{
	    		return;
    		}
    	}
    	
    	$productMap = $this->getProductMapModel($row['type_id'], array(
    		'is_assoc'	  => ($is_assoc !== false ? true : false),
    		'is_apparel'  => ($is_apparel == 1 ? true : false),
    		/*'is_clothing' => ($pMap->isClothingBySql($row['entity_id'], $parentEntityId) == 1 ? true : false),
    		'is_shoes'	  => ($pMap->isShoesBySql($row['entity_id'], $parentEntityId) == 1? true : false),*/
    	));
    	
    	$product = Mage::getModel('catalog/product');
    	$product->setData($row);
    	$product->setStoreId($this->getStoreId());
    	$product->getResource()->load($product, $row['entity_id']);
    	$productMap->setGenerator($this)
    		->setProduct($product)
			->setColumnsMap($this->getColumnsMap())
			->setEmptyColumnsReplaceMap($this->getEmptyColumnsReplaceMap())
			->initialize();
    	
		if ($productMap->checkSkipSubmission()->isSkip())
		{
			if ($this->getConfigVar('log_skip'))
    		{
    			$this->log(sprintf("product id %d product sku %s, skipped - product has 'Skip from Being Submitted' = 'Yes'.", $row['entity_id'], $row['sku']));
    		}
			$this->_count_products_skipped++;
    		return;
		}
    	
        $this->addProductToFeed($productMap);
        
        $productMap->unsetData();
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getCollection()
    {
    	if (is_null($this->_collection))
    	{
    		$this->_collection = clone $this->_getCollection();
    		if ($this->getBatchMode())
	        {
	        	$this->_collection->getSelect()->limit($this->getBatch()->getLimit(), $this->getBatch()->getOffset() - $this->getBatch()->getLimit());
	        }
	        elseif ($this->getTestMode())
	        {
	        	if ($this->getTestSku())
	        	{
	        		$this->_collection->addAttributeToFilter('sku', $this->getTestSku());
	        	}
	        	elseif ($this->getTestOffset() >= 0 && $this->getTestLimit() > 0)
	        	{
	        		$this->_collection->getSelect()->limit(($this->getTestLimit() > 0 ? $this->getTestLimit() : 0), ($this->getTestOffset() > 0 ? $this->getTestOffset() : 0));
	        	}
	        	else{
	        		Mage::throwException(sprintf("Invalid parameters for test mode: sku %s or offset %s and limit %s", $this->getTestSku(), $this->getTestOffset(), $this->getTestLimit()));
	        	}
	        }
    	}
    	
    	return $this->_collection;
    }
    
    protected function _getCollection()
    {
    	$collection = Mage::getModel('catalog/product')->getCollection();
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection*/
        $collection->setStore($this->getData('store_code'));
        //$collection->addAttributeToSelect('name', 'image', 'media_gallery', 'gallery');
        $this->addProductTypeToFilter($collection);
        
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        
        $product_types = $this->getConfig()->getMultipleSelectVar('product_types', $this->getData('store_id'));
        $minimal_price_type = array(
        	Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
			Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
		);
		if (count(array_intersect($minimal_price_type, $product_types)) > 0) {
			// for configurables, bundles, grouped - minimal price, .. => doesn't select out of stock products.
        	$collection->addPriceData(null, $this->getData('website_id'));
		}
        if (!$this->getTestMode() && $this->getConfigVar('sku', 'debug') != "")
        {
        	$collection->addAttributeToFilter('sku', $this->getConfigVar('sku', 'debug'));
        }
        
        return $collection;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected function addProductTypeToFilter($collection)
    {
    	$default_product_types = array(
    		Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
    		Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
    		Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    		Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
    		Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
    		Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        );
		$product_types = $this->getConfigVar('product_types');
		$product_types = explode(",", $product_types);
		$not_in_product_types = array_diff($default_product_types, $product_types);
		
		if (count($not_in_product_types) > 0)
			$collection->addAttributeToFilter('type_id', array('nin' => $not_in_product_types));
		
    	return $collection;
    }
    
    /**
     * Returns columns map in asc order.
     * Skips columns with attributes that doesn't exist.
     * Caches eav attributes model used.
     * 
     *  [column] =>
     * 			[column]
     * 			[attribute code or directive code]
     * 			[default_value]
     * 			[order]
     *
     * @return array
     */
    protected function getColumnsMap()
    {
    	if (!is_null($this->_columns_map))
    		return $this->_columns_map;

    	// Load attributes used. Exclude columns with attributes that doesn't exist.
    	$product = Mage::getModel('catalog/product')->setStoreId($this->getData('store_id'));
    	$tmp = $cfg_map = $this->getConfigVar('map_product_columns', 'columns');
    	foreach ($tmp as $k => $arr)
    	{
    		if (!$this->getConfig()->isDirective($arr['attribute'], $this->getData('store_id')))
    		{
    			$attribute = $product->getResource()->getAttribute($arr['attribute']);
    			if ($attribute == false)
    			{
    				$this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $arr['column'], $arr['attribute']), Zend_Log::WARN);
    				unset($cfg_map[$k]);
    				continue;
    			}
			    $attribute->setStoreId($this->getData('store_id'));
			    $this->_attributes[$attribute->getAttributeCode()] = $attribute;
    		}
    	}
    	$this->_columns_map = array();
    	foreach ($cfg_map as $arr)
    		$this->_columns_map[$arr['column']] = $arr;
    	
    	// Check attribute assigned to manufacturer column.
    	if (isset($this->_columns_map['brand']) && $this->getConfigVar('manufacturer_attribute_code', 'columns') !== "")
    	{
    		$attribute = $product->getResource()->getAttribute($this->getConfigVar('manufacturer_attribute_code', 'columns'));
    		if ($attribute !== false)
    		{
    			$attribute->setStoreId($this->getData('store_id'));
    			$this->_attributes[$attribute->getAttributeCode()] = $attribute;
    		}
    		else
    		{
    			$this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $this->_columns_map['brand']['column'], $this->getConfigVar('manufacturer_attribute_code', 'columns')), Zend_Log::WARN);
				unset($this->_columns_map['brand']);
    		}
    	}
    	elseif (isset($this->_columns_map['brand']) && (!isset($this->_columns_map['brand']['default_value']) || (isset($this->_columns_map['brand']['default_value']) && $this->_columns_map['brand']['default_value'] == "")))
    	{
    		$this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $this->_columns_map['brand']['column'], $this->getConfigVar('manufacturer_attribute_code', 'columns')), Zend_Log::WARN);
			unset($this->_columns_map['brand']);
    	}
    	
    	$names = array('color', 'size', 'gender', 'age_group', 'material', 'pattern');
    	foreach ($names as $n)
    	{
	    	// Check and load apparel attributes.
	    	if (isset($this->_columns_map[$n]) && isset($this->_columns_map[$n]['attribute']) && $this->_columns_map[$n]['attribute'] == 'rw_gbase_directive_apparel_'.$n)
	    	{
	    		if (!$this->loadApparelAttributes($n) && (isset($this->_columns_map[$n]['defailt_value']) && $this->_columns_map[$n]['default_value'] == ""))
	    		{
	    			$this->log(sprintf("Column '%s' ignored, can't find any attributes assigned.", $this->_columns_map[$n]['column']), Zend_Log::WARN);
					unset($this->_columns_map[$n]);
	    		}
	    	}
    	}
		
    	// Check attribute assigned to availability column (stock status).
    	if (!$this->getConfigVar('use_default_stock', 'columns') && isset($this->_columns_map['availability']) && $this->getConfigVar('stock_attribute_code', 'columns') !== "")
    	{
    		$attribute = $product->getResource()->getAttribute($this->getConfigVar('stock_attribute_code', 'columns'));
    		if ($attribute !== false)
    		{
    			$attribute->setStoreId($this->getData('store_id'));
    			$this->_attributes[$attribute->getAttributeCode()] = $attribute;
    		}
    		else
    		{
    			$this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $this->_columns_map['availability']['column'], $this->getConfigVar('stock_attribute_code', 'columns')), Zend_Log::WARN);
				unset($this->_columns_map['availability']);
    		}
    	}
    	
    	$s = array();
    	foreach ($this->_columns_map as $column => $arr)
    		$s[$column] = $arr['order'];
    	array_multisort($s, $this->_columns_map);
		
    	return $this->_columns_map;
    }
    
    /**
     * @param string $name
     * @return bool
     */
    protected function loadApparelAttributes($name)
    {
    	$one = false;
    	$product = Mage::getModel('catalog/product')->setStoreId($this->getData('store_id'));
    	
    	if ($name != "material" && $name != "pattern")
    	{
	    	$attributes_codes = $this->getConfig()->getMultipleSelectVar($name.'_attribute_code', $this->getData('store_id'), 'apparel');
	    	if (count($attributes_codes) > 0)
			{
				foreach ($attributes_codes as $attr_code)
				{
					$attribute = $product->getResource()->getAttribute($attr_code);
					if ($attribute !== false)
					{
						$attribute->setStoreId($this->getData('store_id'));
						$this->_attributes[$attribute->getAttributeCode()] = $attribute;
						$one = true;
					}
				}
			}
    	}
		
    	if ($name != "gender" && $name != "age_group")
    	{
			$attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_'.$name.'_attribute_code', $this->getData('store_id'), 'apparel');
	    	if (count($attributes_codes) > 0)
			{
				foreach ($attributes_codes as $attr_code)
				{
					$attribute = $product->getResource()->getAttribute($attr_code);
					if ($attribute !== false)
					{
						$attribute->setStoreId($this->getData('store_id'));
						$this->_attributes[$attribute->getAttributeCode()] = $attribute;
						$one = true;
					}
				}
			}
    	}
    	
		return $one;
    }
    
    /**
     * Returns columns map replaced by other attributes when it's value is empty for a product.
     * Sorts result asc by rule order.
     * Caches eav attributes model used.
     * Skips rules with attributes that doesn't exist.
     * 
     *
     * @return array
     */
    protected function getEmptyColumnsReplaceMap()
    {
    	$_columns_map = $this->getColumnsMap();
    	if (!is_null($this->_empty_columns_replace_map))
    		return $this->_empty_columns_replace_map;
    	
    	$product = Mage::getModel('catalog/product')->setStoreId($this->getData('store_id'));
    	$tmp = $cfg_map = $this->getConfigVar('map_replace_empty_columns', 'columns');

    	if (empty($cfg_map))
    		$tmp = $cfg_map = array();
    	foreach ($tmp as $k => $arr)
    	{
    		if (!isset($_columns_map[$arr['column']]))
    		{
    			unset($cfg_map[$k]);
    			continue;
    		}
    		
    		$attribute = $product->getResource()->getAttribute($arr['attribute']);
			if ($attribute == false)
			{
				$this->log(sprintf("Rule ('%s', '%s', '%d') is ignored, can't find attribute with code '%s'.", $arr['column'], $arr['attribute'], @$arr['rule_order'], $arr['attribute']), Zend_Log::WARN);
				unset($cfg_map[$k]);
				continue;
			}
		    $attribute->setStoreId($this->getData('store_id'));
		    $this->_attributes[$attribute->getAttributeCode()] = $attribute;
    	}
    	
    	$this->_empty_columns_replace_map = $cfg_map;
    	
    	$s = array();
    	// Move rules without order to the bottom.
    	foreach ($this->_empty_columns_replace_map as $k => $arr)
    	{
    		if (!isset($arr['rule_order']) || (isset($arr['rule_order']) && $arr['rule_order'] == ""))
				$this->_empty_columns_replace_map[$k]['rule_order'] = 99999;

			$s[$k] = $arr['rule_order'];
    	}
    	array_multisort($s, $this->_empty_columns_replace_map);
    	
    	return $this->_empty_columns_replace_map;
    }
    
    public function getHeader() { return array_combine(array_keys($this->_columns_map), array_keys($this->_columns_map)); }
    
    protected function writeFeed($fields, $add_new_line = true) {

    	// google error: "Too many column delimiters"
    	foreach ($this->_columns_map as $column => $arr)
    	{
    		if (isset($fields[$column]) && $fields[$column] == "")
    			$fields[$column] = " ";
    	}
    	fwrite($this->getHandle(), ($add_new_line ? PHP_EOL : '').implode("\t", $fields));
    }
    
    /**
    * @param  RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract $productMap
    */
    protected function addProductToFeed($productMap)
    {
    	try {
	        $rows = $productMap->map();
			if ($productMap->isSkip())
			{
				$this->_count_products_skipped++;
				return $this;
			}
			
			if ($productMap->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE &&
	        	$productMap->getIsApparel() && $productMap->hasAssocMaps() && $productMap->getIsVariants())
        	{
        		foreach ($productMap->getAssocMaps() as $assocMap)
        		{
        			if ($assocMap->isSkip())
        				$this->_count_products_skipped++;
        		}
        	}
        	
	        foreach ($rows as $row)
	        {
	        	// format prices
	        	foreach ($row as $column => $value)
	        	{
	        		if (($column == "price" || $column == "sale_price") && trim($value) != "")
	        		{
	        			$row[$column] = $this->formatPrice($value);
	        		}
	        	}
	        	
				$this->writeFeed($row);
	        	$this->_count_products_exported++;
	        }
    	}
    	catch (Exception $e)
    	{
    		$this->log($e->getMessage(), Zend_Log::ERR);
    		if ($this->getTestMode())
    		{
    			if ($productMap instanceof RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract && $productMap->getProduct() instanceof Mage_Catalog_Model_Product)
    			{
    				Mage::throwException(sprintf("product id %d product sku %s: ".$e->getMessage(), $productMap->getProduct()->getId(), $productMap->getProduct()->getSku()));
    			}
    			else
    			{
    				Mage::throwException($e->getMessage());
    			}
    		}
    	}
        
        return $this;
    }
    
    protected function formatPrice($value)
    {
    	if (trim($value) == "")
    		return $value;

		if (!is_numeric($value)) {
			// no vars or objects references in function getNumber
		    $value = Mage::app()->getLocale()->getNumber($value);
		}
		elseif (is_string($value))
		{
			$value = floatval($value);
		}
		
		$base_currency_code = Mage::getStoreConfig('currency/options/base', $this->getStoreId());
		$to_currency_code = Mage::app()->getStore($this->getStoreId())->getCurrentCurrencyCode();
		if ($base_currency_code != $to_currency_code)
		{
			if (is_null($this->_currencyRate))
			{
				$this->_currencyRate = Mage::app()->getStore()->getCurrentCurrency()->
					getCurrencyRates(
						$base_currency_code,
						$to_currency_code
					);
				
				if (!(is_array($this->_currencyRate) && isset($this->_currencyRate[$to_currency_code])))
				{
					Mage::throwException(sprintf('Can\'t find currency rate %s to %s', $base_currency_code, $to_currency_code));
				}
			}
			$value = $this->_currencyRate[$to_currency_code] * $value;
		}
		
		if (is_null($this->_currencyObject))
		{
			$this->_currencyObject = new Zend_Currency($to_currency_code,
				Mage::getStoreConfig('general/locale/code', $this->getStoreId()));
		}
		
		$options = array(
			'display' => Zend_Currency::NO_SYMBOL,
		);
		$value = sprintf("%F", $value);
		$value = $this->_currencyObject->toCurrency($value, $options);

    	return $value;
    }

    public function isAlternateFeedPath()
    {
    	$path = trim($this->getConfigVar('alternate_feed_dir'));
    	if (!empty($path))
    		return true;
    	return false;
    }

    /**
     * Gets feed's filepath.
     *
     * @return string
     */
    public function getFeedPath()
    {
    	if (!$this->isAlternateFeedPath())
    	{
	    	$filepath = rtrim(Mage::getConfig()->getTempVarDir(), DS) . DS . rtrim($this->getConfigVar('feed_dir'), DS) . DS;
    	}
    	else
    	{
    		if (is_null($this->_alternate_feed_dir))
    		{
    			$this->_setAlternateRealPath();
    		}
    		$filepath = $this->_alternate_feed_dir;
    	}
    	
    	if (!$this->getTestMode())
    		$name = sprintf($this->getConfigVar('feed_filename'), $this->getData('store_code'));
    	else
    		$name = sprintf($this->getConfigVar('test_feed_filename'), $this->getData('store_code'));
    		
    	$filepath .= $name;
    	
    	return $filepath;
    }
    
    protected function _setAlternateRealPath()
    {
    	$base_dir = rtrim(Mage::getConfig()->getBaseDir(), DS);
    	$path = rtrim(trim($this->getConfigVar('alternate_feed_dir')), DS);
    	
    	if (!is_null($this->_alternate_feed_dir) && is_dir($base_dir . DS . $path . DS))
    	{
    		$this->_alternate_feed_dir = $path . DS;
    		return $this->_alternate_feed_dir;
    	}
    	
    	if (is_dir($base_dir . DS . $path . DS))
    		$this->_alternate_feed_dir = $base_dir . DS . $path . DS;
    	else
    		$this->_alternate_feed_dir = false;
    	
    	return $this->_alternate_feed_dir;
    }
    
    protected function initSavePath()
    {
    	if (!$this->isAlternateFeedPath())
        {
	    	$path = dirname($this->getFeedPath());
	    	$ioAdapter = new Varien_Io_File();
			if (!is_dir($path))
			{
				$ioAdapter->mkdir($path);
				if (!is_dir($path))
				{
					Mage::throwException(sprintf('Not enough permissions, can\'t create dir %s.', $path));
				}
			}
        }
        else
        {
        	if ($this->_setAlternateRealPath() === false)
        	{
        		Mage::throwException(sprintf('Can\'t save feed. Dir %s doesn\'t exist.', trim($this->getConfigVar('alternate_feed_dir'))));
        	}
        	else
        	{
        		if (!is_writable(dirname($this->getFeedPath())))
        		{
        			Mage::throwException(sprintf('Can\'t save feed. Dir %s hasn\'t enough permissions.', dirname($this->getFeedPath())));
        		}
        	}
        }
    }
    
    protected function getHandle()
    {
    	if ($this->_handle === null)
    	{
    		$mode = "a";
    		if(!$this->getBatchMode() || ($this->getBatchMode() && $this->getBatch()->getIsNew()))
	    		$mode = "w";

    		$this->_handle = @fopen($this->getFeedPath(), $mode);
    		if ($this->_handle === false)
    		{
				Mage::throwException(sprintf('Not enough permissions to write to file %s.', $this->getFeedPath()));
    		}
    	}
    	
    	return $this->_handle;
    }
    
    protected function closeHandle()
    {
    	@fclose($this->_handle);
    }
    
    public function getCountProductsExported()
    {
    	return $this->_count_products_exported;
    }
    
    public function getCountProductsSkipped()
    {
    	return $this->_count_products_skipped;
    }
    
    public function getAttribute($code)
    {
    	if (isset($this->_attributes[$code]))
    		return $this->_attributes[$code];
    	return false;
    }
    
    protected function getLogCountStep($total)
    {
    	$step = 1000;
    	if ($total <= 0) return $step;
    	if ($total >= 50000) $step = 2500;
    	elseif ($total >= 10000) $step = 1000;
    	elseif ($total >= 1000) $step = 100;
    	elseif ($total >= 500) $step = 50;
    	elseif ($total <= 500 && $step > 10) $step = 10;
    	else $step = 1;
    	return $step;
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Config
     */
    public function getConfig()
    {
    	return Mage::getSingleton('googlebasefeedgenerator/config');
    }
    
    /**
     * @param string $key
     * @param string $section
     * @return mixed
     */
    public function getConfigVar($key, $section = 'settings')
    {
    	return $this->getConfig()->getConfigVar($key, $this->getData('store_id'), $section);
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Tools
     */
    public function getTools()
    {
    	return Mage::getSingleton('googlebasefeedgenerator/tools');
    }
    
    public function log($msg, $level = null)
    {
    	if (is_null($level))
    		$level = Zend_Log::INFO;
    	if (!$this->hasData('force_log'))
    	{
    		$this->setData('force_log', false);
    		if ($this->getConfigVar('force_log'))
    			$this->setData('force_log', true);
    	}
    	
    	if (!$this->hasData('log_filename'))
    		$this->setData('log_filename', sprintf($this->getConfigVar('log_filename'), $this->getData('store_code')));
		
    	if ($this->getBatchMode())
		{
    		$msg = sprintf('[%s] '.$msg, $this->getBatch()->getScheduleId());
		}
		
		$m = memory_get_usage();
		$units = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
		$mem = @round($m / pow(1024, ($i = floor(log($m, 1024)))), 2).' '.$units[$i];
    	$msg = sprintf('(mem %4.2f %s) ', $mem, $units[$i]).$msg;
		
		Mage::log($msg, $level, $this->getData('log_filename'), $this->getData('force_log'));
    }
}