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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Associated_Apparel extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple_Apparel
{
	public function _beforeMap()
	{
    	return $this;
    }
    
    public function _afterMap($rows)
    {
    	reset($rows);
		$fields = current($rows);
		
		if ($this->getConfigVar('for_us', 'apparel') && !$this->checkUsRequired($fields))
		{
			$this->skip = true;
			return $this;
		}
    	return $this;
    }
	
	public function mapColumnDescription($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	// get value from this child first
    	$value = $this->getCellValue($args);
    	
    	if ($value == "")
    		$value = $this->getParentMap()->mapColumn('description');
		
		return $value;
	}
	
	public function mapColumnLink($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	$value = $this->getParentMap()->mapColumn('link');
		return $value;
	}

	public function mapColumnSalePrice($params = array())
	{
    	$cell = "";
    	if (!$this->getParentMap()->hasSpecialPrice())
    		return $cell;
    	
    	$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
		
    	$helper = Mage::helper('googlebasefeedgenerator/tax');
    	/* @var $helper Mage_Tax_Helper_Data */
    	/* 0 - excluding tax
    	   1 - including tax */
    	$priceIncludesTax = ( $helper->priceIncludesTax($this->getStoreId()) ? true : false);
    	$includingTax = ($this->getConfigVar('add_tax_to_price', 'columns') ? true : false);
    	$special_price = $this->getPrice() - $this->getParentMap()->getPrice() + $this->getParentMap()->getSpecialPrice();
    	$price = $helper->getPrice($product, $special_price, $includingTax, false, false, null, $this->getStoreId(), $priceIncludesTax);
    	$cell = $price;

    	$cell = $this->cleanField($cell);
    	return $cell;
	}
	
	public function mapColumnSalePriceEffectiveDate($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	$value = $this->getParentMap()->mapColumn('sale_price_effective_date');
		return $value;
	}
	
	public function mapColumnAvailability($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	$value = $this->getParentMap()->mapColumn('availability');
    	// gets out of stock if parent is out of stock
    	if (strcasecmp($this->getConfig()->getOutOfStockStatus(), $value) == 0)
    		return $value;
    	
    	$value = $this->getCellValue($args);
    	
		return $value;
	}
	
	public function mapColumnBrand($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	// get value from parent first
    	$value = $this->getParentMap()->mapColumn('brand');
    	if ($value != "")
    		return $value;
    	
    	$value = $this->getCellValue($args);
		
		return $value;
	}
	
	public function mapColumnGoogleProductCategory($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	// get value from parent first
    	$value = $this->getParentMap()->mapColumn('google_product_category');
    	if ($value != "")
    		return $value;
    	
    	$map_by_category = $this->getConfig()->getMapCategorySorted('google_product_category_by_category', $this->getStoreId());
    	$category_ids = $this->getProduct()->getCategoryIds();
    	if (empty($category_ids))
    		$category_ids = $this->getParentMap()->getProduct()->getCategoryIds();
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
		
    	if ($value == "")
    	{
    		$this->skip = true;
    		if ($this->getConfigVar('log_skip'))
			{
				$this->log(sprintf("product id %d product sku %s, apparel product variant, no google product category: '%s'.", $this->getProduct()->getId(), $this->getProduct()->getSku(), $cell));
			}
    	}
    	
		return $value;
	}
	
	public function mapColumnProductType($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	// get value from parent first
    	$value = $this->getParentMap()->mapColumn('product_type');
    	if ($value != "")
    		return $value;
    	
    	$map_by_category = $this->getConfig()->getMapCategorySorted('product_type_by_category', $this->getStoreId());
    	$category_ids = $this->getProduct()->getCategoryIds();
    	if (empty($category_ids))
    		$category_ids = $this->getParentMap()->getProduct()->getCategoryIds();
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
	
	public function mapDirectiveApparelItemGroupId($params = array())
	{
		// use parent id
		return $this->getParentMap()->mapColumn('id');
	}
	
	public function mapDirectiveApparelColor($params = array())
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
			$cell = str_replace(",", "/", $cell);
    		return $cell;
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_color_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0)
		{
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code)
		{
			$attribute = $this->getGenerator()->getAttribute($attr_code);
			if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
			{
				$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
				break;
			}
		}
		
		if ($cell == "")
		{
			// Try get from current associated simple non variants attributes (least possible case)
			$attributes_codes = $this->getConfig()->getMultipleSelectVar('color_attribute_code', $this->getStoreId(), 'apparel');
			if (count($attributes_codes) == 0)
			{
				return $cell;
			}
			
			foreach ($attributes_codes as $attr_code)
			{
				$attribute = $this->getGenerator()->getAttribute($attr_code);
				if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
				{
					$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
					break;
				}
			}
		}
		
		// Try get from parent configurable, may be a non superattribute value.
		if ($cell == "")
			$cell = $this->getParentMap()->mapColumn('color');
		
		// multiselect attributes - comma replaced with /
		$cell = str_replace(",", "/", $cell);
		
		return $cell;
	}
	
	public function mapDirectiveApparelSize($params = array())
	{
		$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";

    	if (!($this->getIsApparelClothing() || $this->getIsApparelShoes()))
			return $cell;
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "")
    	{
			$cell = $default_value;
    		$cell = $this->cleanField($cell);
    		return $cell;
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_size_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0)
		{
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code)
		{
			$attribute = $this->getGenerator()->getAttribute($attr_code);
			if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
			{
				$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
				break;
			}
		}
		
		if ($cell == "")
		{
			// Try get from current associated simple non variants attributes (least possible case)
			$attributes_codes = $this->getConfig()->getMultipleSelectVar('size_attribute_code', $this->getStoreId(), 'apparel');
			if (count($attributes_codes) == 0)
			{
				return $cell;
			}
			
			foreach ($attributes_codes as $attr_code)
			{
				$attribute = $this->getGenerator()->getAttribute($attr_code);
				if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
				{
					$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
					break;
				}
			}
		}

		// Try get from parent configurable, may be a non superattribute value.
		if ($cell == "")
			$cell = $this->getParentMap()->mapColumn('size');
		
		return $cell;
	}
	
	public function mapDirectiveApparelMaterial($params = array())
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
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_material_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0)
		{
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code)
		{
			$attribute = $this->getGenerator()->getAttribute($attr_code);
			if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
			{
				$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
				break;
			}
		}

		return $cell;
	}
	
	public function mapDirectiveApparelPattern($params = array())
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
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_pattern_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0)
		{
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code)
		{
			$attribute = $this->getGenerator()->getAttribute($attr_code);
			if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
			{
				$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
				break;
			}
		}

		return $cell;
	}
	
	public function mapColumnGender($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	// get value from parent first
    	$value = $this->getParentMap()->mapColumn('gender');
    	if ($value != "")
    		return $value;
    	
    	$value = $this->getCellValue($args);
		
		return $value;
	}
	
	public function mapColumnAgeGroup($params = array())
	{
		$args = array('map' => $params['map']);
    	$value = "";
    	
    	// get value from parent first
    	$value = $this->getParentMap()->mapColumn('age_group');
    	if ($value != "")
    		return $value;
    	
    	$value = $this->getCellValue($args);
		
		return $value;
	}
	
	protected function checkUsRequired($fields)
	{
		foreach ($fields as $k => $v) $fields[$k] = trim($v);
		$gb_category = $this->mapColumn('google_product_category');
		
		$ret = true;
		$empties = array();
		$columns = array('color', 'gender', 'age_group');
		if ($this->getIsApparelClothing() || $this->getIsApparelShoes())
			$columns[] = 'size';
		foreach ($columns as $column)
		{
			if (!isset($fields[$column]) || (isset($fields[$column]) && $fields[$column] == ""))
			{
				if ($column == 'gender' || $column == 'age_group')
				{
					// not required for some subcategories
					$f = false;
					foreach ($this->getConfig()->getMultipleSelectVar($column.'_not_req_categories', $this->getStoreId(), 'apparel') as $categ)
					{
						if ($this->matchGoogleCategory($gb_category, $categ))
							$f = true;
					}
					if (!$f)
					{
						$empties[] = $column;
						$ret = false;
					}
				}
				else
				{
					$empties[] = $column;
					$ret = false;
				}
			}
		}
		
		if (!$ret && $this->getConfigVar('log_skip'))
		{
			$this->log(sprintf("product id %d product sku %s, apparel product variant, columns empty: %s.", $this->getProduct()->getId(), $this->getProduct()->getSku(), implode(",", $empties)));
		}
		
		return $ret;
	}
}