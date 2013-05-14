<?php

abstract class Ip_Ordermanager_Model_Export_Abstractcsv extends Mage_Core_Model_Abstract
{
    /**
     * Definition of abstract method to export orders to a file in a specific format in var/export.
     *
     * @param $orders List of orders of type Mage_Sales_Model_Order or order ids to export.
     * @return String The name of the written file in var/export
     */
    abstract public function exportOrders($orders);

    /**
     * Returns the name of the website, store and store view the order was placed in.
     *
     * @param Mage_Sales_Model_Order $order The order to return info from
     * @return String The name of the website, store and store view the order was placed in
     */
    protected function getStoreName($order) 
    {
        $store = Mage::app()->getStore($order->getStoreId);
        $name = array(
        $store->getWebsite()->getName(),
        $store->getGroup()->getName(),
        $store->getName()
        );
        return implode(', ', $name);
    }

    /**
     * Returns the payment method of the given order.
     *
     * @param Mage_Sales_Model_Order $order The order to return info from
     * @return String The name of the payment method
     */
    protected function getPaymentMethod($order)
    {
        return $order->getPayment()->getMethod();
    }
    
    /**
     * Returns the shipping method of the given order.
     *
     * @param Mage_Sales_Model_Order $order The order to return info from
     * @return String The name of the shipping method
     */
    protected function getShippingMethod($order)
    {
        if (!$order->getIsVirtual() && $order->getShippingDescription()) {
            return $order->getShippingDescription();
        }
        else if (!$order->getIsVirtual() && $order->getShippingMethod()) {
        	return $order->getShippingMethod();
        }
        return '';
    }
    
    /**
     * Returns the total quantity of ordered items of the given order.
     *
     * @param Mage_Sales_Model_Order $order The order to return info from
     * @return int The total quantity of ordered items
     */
    protected function getTotalQtyItemsOrdered($order) {
        $qty = 0;
        $orderedItems = $order->getItemsCollection();
        foreach ($orderedItems as $item)
        {
            if (!$item->isDummy()) {
                $qty += (int)$item->getQtyOrdered();
            }
        }
        return $qty;
    }

    /**
     * Returns the sku of the given item dependant on the product type.
     *
     * @param Mage_Sales_Model_Order_Item $item The item to return info from
     * @return String The sku
     */
    protected function getItemSku($item)
    {
        if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            return $item->getProductOptionByCode('simple_sku');
        }
        return $item->getSku();
    }

    /**
     * Returns the options of the given item separated by comma(s) like this:
     * option1: value1, option2: value2
     *
     * @param Mage_Sales_Model_Order_Item $item The item to return info from
     * @return String The item options
     */
    protected function getItemOptions($item)
    {
        $options = '';
        if ($orderOptions = $this->getItemOrderOptions($item)) {
            foreach ($orderOptions as $_option) {
                if (strlen($options) > 0) {
                    $options .= ', ';
                }
                $options .= $_option['label'].': '.$_option['value'];
            }
        }
        return $options;
    }

    /**
     * Returns all the product options of the given item including additional_options and
     * attributes_info.
     *
     * @param Mage_Sales_Model_Order_Item $item The item to return info from
     * @return Array The item options
     */
    protected function getItemOrderOptions($item)
    {
        $result = array();
        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (!empty($options['attributes_info'])) {
                $result = array_merge($options['attributes_info'], $result);
            }
        }
        return $result;
    }

    /**
     * Calculates and returns the grand total of an item including tax and excluding
     * discount.
     *
     * @param Mage_Sales_Model_Order_Item $item The item to return info from
     * @return Float The grand total
     */
    protected function getItemTotal($item) 
    {
        return $item->getRowTotal() - $item->getDiscountAmount() + $item->getTaxAmount() + $item->getWeeeTaxAppliedRowAmount();
    }

    /**
     * Formats a price by adding the currency symbol and formatting the number 
     * depending on the current locale.
     *
     * @param Float $price The price to format
     * @param Mage_Sales_Model_Order $formatter The order to format the price by implementing the method formatPriceTxt($price)
     * @return String The formatted price
     */
    protected function formatPrice($price, $formatter) 
    {
        return $formatter->formatPriceTxt($price);
    }
    
}