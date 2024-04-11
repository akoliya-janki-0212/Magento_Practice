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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple_Apparel extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple
{
	public function initialize()
	{
		parent::initialize();
		$this->setApparelCategories();
	}
	
	protected function _map()
	{
		$rows = parent::_map();
		reset($rows);
		$fields = current($rows);
		
		return $rows;
	}
	
	public function _afterMap($rows)
	{
		reset($rows);
		$fields = current($rows);
		
		if (!$this->checkSkipImages($fields))
		{
			$this->skip = true;
			return $this;
		}
		
		if ($this->getConfigVar('for_us', 'apparel') && !$this->checkUsRequired($fields))
		{
			$this->skip = true;
			return $this;
		}
		return $this;
	}
	
	public function checkSkipImages($fields)
	{
		if (!$this->getConfigVar('submit_no_img', 'apparel') && (!isset($fields['image_link']) || (isset($fields['image_link']) && trim($fields['image_link']) == "")))
		{
			if ($this->getConfigVar('log_skip'))
			{
				$this->log(sprintf("product id %d product sku %s, skipped - apparel product non variant, no image: '%s'.", $this->getProduct()->getId(), $this->getProduct()->getSku(), trim($fields['image_link'])));
			}
			return false;
		}
		return true;
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

		return $cell;
	}
	
	public function mapDirectiveApparelGender($params = array())
	{
		$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	$allowed_genders = $this->getConfig()->getAllowedGender();
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "")
    	{
    		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($default_value)), $allowed_genders)) == 0)
    		{
				$cell = $default_value;
    			$cell = $this->cleanField($cell);
    			return $cell;
    		}
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('gender_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0 )
		{
			$cell = "";
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code)
		{
			$attribute = $this->getGenerator()->getAttribute($attr_code);
			if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
			{
				$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
				if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_genders)) == 0)
				{
					break;
				}
				else
				{
					$cell = "";
				}
			}
		}
		
		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_genders)) != 0)
			$cell = "";
		
		return $cell;
	}
	
	public function mapDirectiveApparelAgeGroup($params = array())
	{
		$map = $params['map'];
    	$product = $this->getProduct();
    	/* @var $product Mage_Catalog_Model_Product */
    	$cell = "";
    	$allowed_age_groups = $this->getConfig()->getAllowedAgeGroup();
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "")
    	{
    		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($default_value)), $allowed_age_groups)) == 0)
    		{
				$cell = $default_value;
    			$cell = $this->cleanField($cell);
    			return $cell;
    		}
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('age_group_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0 )
		{
			$cell = "";
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code)
		{
			$attribute = $this->getGenerator()->getAttribute($attr_code);
			if ($this->cleanField($this->getAttributeValue($this->getProduct(), $attribute)) != "")
			{
				$cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute));
				if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_age_groups)) == 0)
				{
					break;
				}
				else
				{
					$cell = "";
				}
			}
		}
		
		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_age_groups)) != 0)
			$cell = "";
		
		return $cell;
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
			$this->log(sprintf("product id %d product sku %s, skipped - apparel product non variant, columns empty: %s.", $this->getProduct()->getId(), $this->getProduct()->getSku(), implode(",", $empties)));
		}
		
		return $ret;
	}
}