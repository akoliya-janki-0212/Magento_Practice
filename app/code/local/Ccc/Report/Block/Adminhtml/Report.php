<?php
class Ccc_Report_Block_Adminhtml_Report extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_report';
        $this->_blockGroup = 'ccc_report';
        $this->_headerText = Mage::helper('report')->__('Manage Reports');
        parent::__construct();
    }
     public function getSalesData()
     {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_setup');
         $query = "SELECT 
         so.increment_id AS Order_Number,
         CONCAT(sa_billing.street, ', ', sa_billing.city, ', ', sa_billing.region, ', ', sa_billing.postcode, ', ', sa_billing.country_id) AS Billing_Address,
         CONCAT(sa_shipping.street, ', ', sa_shipping.city, ', ', sa_shipping.region, ', ', sa_shipping.postcode, ', ', sa_shipping.country_id) AS Shipping_Address,
         so.tax_amount AS Tax,
         so.discount_amount AS Discount,
         so.grand_total AS Order_total,
         sp.method AS Payment_Method
         FROM 
             sales_flat_order AS so
         LEFT JOIN 
             sales_flat_order_address AS sa_billing ON so.billing_address_id = sa_billing.entity_id
         LEFT JOIN 
             sales_flat_order_address AS sa_shipping ON so.shipping_address_id = sa_shipping.entity_id
         LEFT JOIN 
             sales_flat_order_payment AS sp ON so.entity_id = sp.parent_id;
         ";
         $result = $connection->fetchAll($query);
         $jsonResult = $this->getJson($result);
         return $jsonResult;
         // Get the sales order collection
         $collection = Mage::getResourceModel('sales/order_collection');

         // Join with order billing address
         $collection->getSelect()->join(
             array('billing' => 'sales_flat_order_address'),
             'main_table.billing_address_id = billing.entity_id',
             array(
                 'Billing_Address' => new Zend_Db_Expr("CONCAT(billing.street, ', ', billing.city, ', ', billing.region, ', ', billing.postcode, ', ', billing.country_id)")
             )
         );

         // Join with order shipping address
         $collection->getSelect()->join(
             array('shipping' => 'sales_flat_order_address'),
             'main_table.shipping_address_id = shipping.entity_id',
             array(
                 'Shipping_Address' => new Zend_Db_Expr("CONCAT(shipping.street, ', ', shipping.city, ', ', shipping.region, ', ', shipping.postcode, ', ', shipping.country_id)")
             )
         );

         // Join with order payment
         $collection->getSelect()->join(
             array('payment' => 'sales_flat_order_payment'),
             'main_table.entity_id = payment.parent_id',
             array('Payment_Method' => 'method')
         );

         // Select specific fields from the main order table
         $collection->addFieldToSelect('increment_id', 'Order_Number');
         $collection->addFieldToSelect('tax_amount', 'Tax');
         $collection->addFieldToSelect('discount_amount', 'Discount');
         $collection->addFieldToSelect('grand_total', 'Order_total');

         // Convert results to JSON
         return $this->getJson($collection->getData());
     }
     public function getJson($data)
     {
         return json_encode($data);
        }
    public function getCollectionData()
    {
        $reportHelper = Mage::helper('report/report');
        $XMlReadedData = $reportHelper->getFieldsetData('ccc_report_columns');
        $reportModel = Mage::getSingleton('ccc_report/report');
        return $reportHelper->getJsonData(
            $reportModel->getConnection(
                $reportModel->prepareQuery($XMlReadedData)
            )
        );
    }
    public function getLabelsData()
    {
        return Mage::helper('report/report')->getLabel();
    }
    public function getDefaultColumns()
    {
        return Mage::helper('report/report')->getDefault();
    }
}
