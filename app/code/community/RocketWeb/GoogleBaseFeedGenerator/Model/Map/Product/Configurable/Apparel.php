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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable_Apparel extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple_Apparel
{
	protected $_is_variants = false;
	protected $_variants_rows = false;
	protected $_original_variants_rows = false;
	protected $_assoc_ids;
	protected $_assocs;
	
	public function initialize()
	{
		parent::initialize();
		$this->setApparelCategories();
	}
	
	public function _beforeMap() {
		$assocMapArr = array();
		/*foreach ($this->getProduct()->getTypeInstance()->getUsedProducts() as $assoc)
		{
			$assocMap = $this->getAssocMapModel($assoc);
			if ($assocMap->checkSkipSubmission()->isSkip())
			{
				if ($this->getConfigVar('log_skip'))
	    		{
	    			$this->log(sprintf("product id %d product sku %s, skipped - product has 'Skip from Being Submitted' = 'Yes'.", $assoc->getId(), $assoc->getSku()));
	    		}
	    		continue;
			}
			$assocMapArr[$assoc->getId()] = $assocMap;
		}*/
		
		$this->_assocs = array();
		foreach ($this->getAssocIds() as $assocId)
		{
			$assoc = Mage::getModel('catalog/product');
	    	$assoc->setStoreId($this->getStoreId());
	    	$assoc->getResource()->load($assoc, $assocId);
	    	$this->_assocs[$assocId] = $assoc;
    	
			$assocMap = $this->getAssocMapModel($assoc);
			if ($assocMap->checkSkipSubmission()->isSkip())
			{
				if ($this->getConfigVar('log_skip'))
	    		{
	    			$this->log(sprintf("product id %d product sku %s, skipped - product has 'Skip from Being Submitted' = 'Yes'.", $assoc->getId(), $assoc->getSku()));
	    		}
	    		continue;
			}
			$assocMapArr[$assoc->getId()] = $assocMap;
		}
		
		$this->setAssocMaps($assocMapArr);
		
    	return $this;
    }
    
    public function getAssocIds()
    {
    	if (is_null($this->_assoc_ids))
		{
			$this->_assoc_ids = array();
			$as = $this->getTools()->getChildsIds($this->getProduct()->getId());
			if ($as === false)
				return $this->_assoc_ids;
			$as = $this->getTools()->getProductInStoresIds($as);
			foreach ($as as $assocId => $s)
			{
				if (is_array($s) && array_search($this->getStoreId(), $s) !== false)
					$this->_assoc_ids[] = $assocId;
			}
		}
		return $this->_assoc_ids;
    }
	
	public function _map()
	{
		$rows = array();
		$this->_variants_rows = array();
		foreach ($this->getAssocMaps() as $assocId => $assocMap)
		{
			$row = $assocMap->map();
			reset($row);
			$row = current($row);
			if (!$assocMap->isSkip())
				$this->_variants_rows[] = $row;
			$rows[] = $row;
		}
		$this->_original_variants_rows = $this->_variants_rows;
		
		$this->_is_variants = $this->validateVariants($this->_variants_rows);
		
		if ($this->_is_variants)
		{
			$rows = $this->_variants_rows;
		}
		else
		{
			if ($this->getConfigVar('for_us', 'apparel'))
			{
				// As stand alone apparel product - no variants.
				$rows = parent::_map();
				$rows = $this->formUsConfigurableNonVariant($rows);
			}
			else
			{
				$rows = parent::_map();
				$rows = $this->formOtherConfigurableNonVariant($rows);
			}
		}
		
		return $rows;
	}
	
	public function _afterMap($rows)
	{
		if (!$this->_is_variants)
		{
			parent::_afterMap($rows);
		}
		return $this;
	}
	
	/**
     * @param Mage_Catalog_Model_Product $product
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
     */
    protected function getAssocMapModel($product)
    {
    	$params = array(
    		'store_code' => $this->getData('store_code'),
    		'store_id' => $this->getData('store_id'),
    		'website_id' => $this->getData('website_id'),
    	);
    	
    	$productMap = Mage::getModel('googlebasefeedgenerator/map_product_associated_apparel', $params);
    	
    	/*$assoc = Mage::getModel('catalog/product');
    	$assoc->setStoreId($this->getStoreId());
    	$assoc->getResource()->load($assoc, $productId);*/
    	$productMap->setGenerator($this->getGenerator())
    		->setProduct($product)
			->setColumnsMap($this->_columns_map)
			->setEmptyColumnsReplaceMap($this->_empty_columns_replace_map)
			->setParentMap($this)
			->initialize();
    	
    	return $productMap;
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
		
		// Configurable doesn't have weight of it own. Get value from first associated product that matches minimal price.
		if ($weight == "")
		{
			foreach ($this->_assocs as $assoc)
			{
				if ($this->getProduct()->getMinimalPrice() == $assoc->getFinalPrice())
				{
					$weight = $weight = $this->getAttributeValue($assoc, $weight_attribute);
					break;
				}
			}
		}
		
		if ($weight != "")
			$weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
		
		$cell = $weight;
    	
    	$cell = $this->cleanField($cell);
    	return $cell;
    }
    
    public function validateVariants(&$rows)
    {
    	$is_variants = true;
    	$only_varies = array();
    	$only_varies_all = array('material', 'pattern');
    	foreach ($only_varies_all as $column)
    	{
    		if (isset($this->_columns_map[$column]))
    			$only_varies[] = $column;
    	}
    	unset($only_varies_all);
    	
    	$changed = false;
    	foreach ($only_varies as $column)
    	{
    		$tt = array();
	    	foreach ($rows as $line => $row)
	    	{
	    		if (isset($row[$column]) && $row[$column] != "")
	    			$tt[strtolower($row[$column])] = true;
	    	}
	    	
	    	// Doesn't vary, no value in feed.
	    	if (count($tt) <= 1)
	    	{
	    		foreach ($rows as $line => $row)
	    			$rows[$line][$column] = "";
	    	}
    	}
    	
    	reset($rows);
    	$row = current($rows);
    	$gb_category = (isset($row['google_product_category']) ? $row['google_product_category'] : "");
    	$must_vary = array('color');
    	if (isset($this->_columns_map['material']))
			$must_vary[] = 'material';
		if (isset($this->_columns_map['pattern']))
			$must_vary[] = 'pattern';
		
    	if ($this->matchApparelClothingCategory($gb_category) || $this->matchApparelShoesCategory($gb_category))
    		$must_vary[] = 'size';

    	// More than 1 line for a combination of variants values => choose by image and minimal price.
    	$variants_values = array();
    	$configurable_image = $this->mapColumn('image_link');
    	foreach ($rows as $line => $row)
    	{
    		$tt = "";
			foreach ($must_vary as $column)
				$tt .= $row[$column];
			if (!isset($variants_values[$tt]))
				$variants_values[$tt] = array();
			$variants_values[$tt][] = $line;
    	}
    	
    	foreach ($variants_values as $v)
    	{
    		if (count($v) > 0)
    		{
    			$keep = false;
    			$minimal_price = 99999999;
    			foreach ($v as $line)
    			{
    				// It should have and image and is minimal price.
    				if ((!$this->getConfigVar('variant_submit_no_img', 'apparel') && isset($rows[$line]['image_link']) && $rows[$line]['image_link'] != "") && $rows[$line]['price'] < $minimal_price)
    				{
    					$keep = $line;
    					$minimal_price = $rows[$line]['price'];
    				}
    			}
    			
    			if ($keep !== false)
    			{
    				foreach ($v as $line)
    				{
    					if ($keep !== $line)
    					{
    						unset($rows[$line]);
    						$changed = true;
    					}
    				}
    			}
    			else
    			{
    				// Get image from configurable.
    				$keep = false;
    				$minimal_price = 99999999;
    				foreach ($v as $line)
	    			{
	    				if ($rows[$line]['price'] < $minimal_price)
	    				{
	    					$keep = $line;
	    					$minimal_price = $rows[$line]['price'];
	    				}
	    			}
	    			
	    			foreach ($v as $line)
    				{
    					if ($keep !== $line)
    					{
    						unset($rows[$line]);
    						$changed = true;
    					}
    				}
    				
    				$rows[$keep]['image_link'] = $configurable_image;
    			}
    		}
    	}
    	
    	if ($this->getConfigVar('submit_no_img', 'apparel') && $this->getConfigVar('variant_submit_no_img', 'apparel'))
    	{
    		// No image -> get configurable image.
	    	foreach ($rows as $line => $row)
	    	{
	    		if (isset($rows[$line]['image_link']) && $rows[$line]['image_link'] == "")
	    		{
	    			$rows[$line]['image_link'] = $configurable_image;
	    		}
	    	}
    	}
    	
    	if (!$this->getConfigVar('submit_no_img', 'apparel'))
    	{
    		// Should have at least configurable image.
    		$crows = $rows;
    		foreach ($crows as $line => $row)
    		{
    			if (!isset($row['image_link']) || (isset($row['image_link']) && $row['image_link'] == ""))
	    		{
	    			unset($rows[$line]);
	    			$changed = true;
	    		}
    		}
    	}
    	
    	if (count($rows) <= 1)
    	{
    		$is_variants = false;
    		// no variants, clear item_group_id
    		foreach ($rows as $line => $row)
    		{
    			if (isset($row['item_group_id']))
    				$rows[$line]['item_group_id'] = "";
    		}
    	}
    	else
    	{
    		if ($changed)
    		{
    			// Change title and description with configurable data
    			$varies = array('color');
    			if ($this->matchApparelClothingCategory($gb_category) || $this->matchApparelShoesCategory($gb_category))
    				$varies[] = 'size';
    			
	    		if (isset($this->_columns_map['material']))
					$varies[] = 'material';
				if (isset($this->_columns_map['pattern']))
					$varies[] = 'pattern';
    			
    			$parent_title = $this->mapColumn('title');
    			$parent_description = $this->mapColumn('description');
    			foreach ($rows as $line => $row)
    			{
    				if (isset($row['description']) && $row['description'] == "")
    					$rows[$line]['description'] = $parent_description;
    				
    				if (isset($row['title']))
    					$rows[$line]['title'] = $parent_title;
    			}
    		}
    	}
    	
    	return $is_variants;
    }
    
    protected function formUsConfigurableNonVariant($rows)
    {
    	reset($rows);
    	$fields = current($rows);

    	$gb_category = (isset($fields['google_product_category']) ? $fields['google_product_category'] : "");
    	$must_have = array('color');
    	if ($this->matchApparelClothingCategory($gb_category) || $this->matchApparelShoesCategory($gb_category))
    		$must_have[] = 'size';
    	
    	// If empty color or size try replace configurable with valid associated product color/size/price that has minimal price.
    	$minimal_price = 99999999;
    	$keep = false;
    	if ((isset($fields['color']) && $fields['color'] == "") || (array_search("size", $must_have) !== false && isset($fields['size']) && $fields['size'] == ""))
    	{
    		foreach ($this->_original_variants_rows as $line => $row)
    		{
    			$all = true;
    			foreach ($must_have as $column)
    			{
    				if (!isset($row[$column]) || (isset($row[$column]) && $row[$column] == ""))
    				$all = false;
    			}
    			
    			if ($all && $row['price'] < $minimal_price)
    			{
    				$keep = $line;
    				$minimal_price = $row['price'];
    			}
    		}
    	}
    	
    	if ($keep !== false && $this->_original_variants_rows[$keep]['image_link'] == "")
    	{
    		// Get configurable image.
    		$configurable_image = $this->mapColumn('image_link');
    		if ($configurable_image != "")
    		{
    			$this->_original_variants_rows[$keep]['image_link'] = $configurable_image;
    		}
    		else
    		{
    			$keep = false;
    		}
    	}
    	
    	if ($keep !== false)
    	{
    		if (isset($fields['color']) && $fields['color'] == "")
    			$fields['color'] = $this->_original_variants_rows[$keep]['color'];
    		
    		if (isset($fields['size']) && $fields['size'] == "")
    			$fields['size'] = $this->_original_variants_rows[$keep]['size'];
    		
    		if ($this->_original_variants_rows[$keep]['price'] > 0)
    			$fields['price'] = $this->_original_variants_rows[$keep]['price'];
    		
    		if ($this->_original_variants_rows[$keep]['sale_price'] > 0)
    		{
    			$fields['sale_price'] = $this->_original_variants_rows[$keep]['sale_price'];
    			if ($this->_original_variants_rows[$keep]['sale_price_effective_date'] != "")
    				$fields['sale_price_effective_date'] = $this->_original_variants_rows[$keep]['sale_price_effective_date'];
    		}
    		
    		// Configurable does not have weight of it's own, fill with child's weight.
    		if (isset($fields['shipping_weight']))
    		{
    			$fields['shipping_weight'] = $this->_original_variants_rows[$keep]['shipping_weight'];
    		}
    	}
    	else
    	{
    		// Pass intact configurable values.
    	}
    	
    	return array($fields);
    }
    
    protected function formOtherConfigurableNonVariant($rows)
    {
    	reset($rows);
    	$fields = current($rows);
    	
    	// compact apparel fields
    	$varies = array('color', 'size', 'material', 'pattern', 'gender', 'age_group');
    	foreach ($varies as $column)
    	{
    		if (isset($fields[$column]))
    		{
    			$values = array();
    			if ($fields[$column] != "")
    			{
    				$arr = explode(",", $fields[$column]);
    				foreach ($arr as $k => $v)
    					$values[trim($v)] = trim($v);
    			}
    			
    			foreach ($this->_variants_rows as $line => $row)
				{
					if (isset($row[$column]) && $row[$column] != "")
					{
						$arr = explode(",", $row[$column]);
						foreach ($arr as $k => $v)
							$values[trim($v)] = trim($v);
					}
				}
				
				$fields[$column] = implode(",", $values);
    		}
    	}

    	return array($fields);
    }
    
    protected function getPrice()
    {
    	if (!$this->hasSpecialPrice())
    	{
    		return $this->getProduct()->getMinimalPrice();
    	}
    	else
    	{
    		return $this->getProduct()->getPrice();
    	}
    }
    
    public function getIsVariants()
    {
    	$this->_is_variants;
    }
    
    public function genUnsetData()
    {
    	foreach ($this->getAssocMaps() as $assocId => $assocMap)
    	{
    		$assocMap->genUnsetData();
    	}

    	unset($this->_data);
    	unset($this->_origData);
    }
}