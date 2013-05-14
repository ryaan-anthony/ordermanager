<?php

class Ip_Ordermanager_Model_Export_Csv extends Ip_Ordermanager_Model_Export_Abstractcsv
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';

    /**
     * Concrete implementation of abstract method to export given orders to csv file in var/export.
     *
     * @param $orders List of orders of type Mage_Sales_Model_Order or order ids to export.
     * @return String The name of the written csv file in var/export
     */
    public function exportOrders($orders) 
    {
        $fileName = 'order_export_'.date("Ymd_His").'.csv';
        $fp = fopen(Mage::getBaseDir('export').'/'.$fileName, 'w');
        
        $this->writeHeadRow($fp);
        foreach ($orders as $order) {
        	$order = Mage::getModel('sales/order')->load($order);
            $this->writeOrder($order, $fp);
        }
		
        fclose($fp);
        return $fileName;
    }

    /**
	 * Writes the head row with the column names in the csv file.
	 * 
	 * @param $fp The file handle of the csv file
	 */
    protected function writeHeadRow($fp) 
    {
        fputcsv($fp, $this->getHeadRowValues(), self::DELIMITER, self::ENCLOSURE);
    }

    /**
	 * Writes the row(s) for the given order in the csv file.
	 * A row is added to the csv file for each ordered item. 
	 * 
	 * @param Mage_Sales_Model_Order $order The order to write csv of
	 * @param $fp The file handle of the csv file
	 */
    protected function writeOrder($order, $fp) 
    {
        $common = $this->getCommonOrderValues($order);
		fputcsv($fp, $common, self::DELIMITER, self::ENCLOSURE);
    }

    /**
	 * Returns the head column names.
	 * 
	 * @return Array The array containing all column names
	 */
    protected function getHeadRowValues() 
    {
        return array(
            'Order #',
			'Purchased On',
			'Bill to Name',
			'Ship to Name',
			'G.T. (Base)',
			'G.T. (Purchased)',
			'Status',
			'Message',
			'Payment Method',
			'Shipping Method'
    	);
    }

    /**
	 * Returns the values which are identical for each row of the given order. These are
	 * all the values which are not item specific: order data, shipping address, billing
	 * address and order totals.
	 * 
	 * @param Mage_Sales_Model_Order $order The order to get values from
	 * @return Array The array containing the non item specific values
	 */
    protected function getCommonOrderValues($order) 
    {
		//$payment = $order->getPayment()->getMethodInstance();
		$shipping = !$order->getIsVirtual() ? $order->getShippingAddress() : null;
    	$billing = $order->getBillingAddress();
		
        $gift_message = '';
        if($gift_message_id = $order->getGiftMessageId()){
          $message = Mage::getModel('giftmessage/message')->load($gift_message_id);
          $gift_message = $message->getData('message');
        }
			
        return array(					 
            $order->getRealOrderId(),
			Mage::helper('core')->formatDate($order->getCreatedAt(), 'medium', true),
			$billing->getName(),
			$shipping ? $shipping->getName() : $billing->getName(),
            $this->formatPrice($order->getData('base_grand_total'), $order),
            $this->formatPrice($order->getData('grand_total'), $order),
			$order->getStatus(),
			$gift_message,
	        $this->getPaymentMethod($order),
	        $this->getShippingMethod($order),
        );
    }

}

?>