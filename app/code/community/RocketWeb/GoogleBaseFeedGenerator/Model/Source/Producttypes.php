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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Producttypes extends Varien_Object
{
    public function toOptionArray()
    {
        $vals = array(
    		Mage_Catalog_Model_Product_Type::TYPE_BUNDLE			=> ucwords(Mage_Catalog_Model_Product_Type::TYPE_BUNDLE),
    		Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE		=> ucwords(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE),
    		Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE	=> ucwords(Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE),
    		Mage_Catalog_Model_Product_Type::TYPE_GROUPED			=> ucwords(Mage_Catalog_Model_Product_Type::TYPE_GROUPED),
    		Mage_Catalog_Model_Product_Type::TYPE_SIMPLE			=> ucwords(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) . ' (all)',
    		Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL			=> ucwords(Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL),
    		RocketWeb_GoogleBaseFeedGenerator_Model_Generator::PRODUCT_TYPE_ASSOC => 'Simple Associated to Configurable',
        );
        $options = array();
        foreach ($vals as $k => $v)
            $options[] = array('value' => $k, 'label' => $v);

        return $options;
    }
}