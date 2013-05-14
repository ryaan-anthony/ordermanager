<?php
/**
 * Magento Ip Order Export Module
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
 * @category   Ip
 * @package    Ip_OrderExport
 * @copyright  Copyright (c) 2010 Zowta Ltd (http://www.webshopapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Genevieve Eddison <sales@webshopapps.com>
 * */

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
            'Order Number',
			'Customer ID',
            'Order Payment Method',
    		'Payment Type',
    		'Credit Card Name',
    		'Credit Card Number',
    		'Credit Card Expiration',
    		'Bank',
    		'Check Account',
    		'Check Routing',
    		'Check Num'
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
        
		$db = Mage::getSingleton('core/resource')->getConnection('core_read');
		$cc_type="";
		$cc_name="";
		$cc_number="";
		$cc_expire="";
		$cc_cvc="";		
		$checkmo_bank = "";
		$checkmo_acct = "";
		$checkmo_route = "";
		$checkmo_number = "";
										   
		//Cust ID
		$customer_id = $order->getCustomerId();
		
		//Payment method
		$payment = $order->getPayment()->getMethodInstance();
		if(!$payment){
			$paymethod = "depreciated";
		}else{
			$paymethod = $payment->getCode();
		}
		
		
		if($paymethod == "cod"){
			
		} elseif(in_array($paymethod,array("check_new","check_onfile","checkmo"))){
			$checkmo_bank = $order->getPayment()->getData('echeck_bank_name');
			$checkmo_acct = $order->getPayment()->getData('echeck_account_name');
			$checkmo_route = $order->getPayment()->getData('echeck_routing_number');
			$checkmo_number = $order->getPayment()->getData('echeck_type');
		} else{
								
			//Expire
			$month = $order->getPayment()->getCcExpMonth();
			$year = $order->getPayment()->getCcExpYear();
			$cc_expire = date('m/y', strtotime($month.'/1/'.$year));
			
			//CC Number
			if(!$cc_number = $order->getPayment()->getCcNumber()){
				$cc_number = $order->getPayment()->getCcLast4();	
			}
		}
		
		if($order->getState() == Mage_Sales_Model_Order::STATE_NEW){
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
		}		
			
        return array(					 
            $order->getRealOrderId(),
			$customer_id,
            $paymethod,
			$order->getPayment()->getData('cc_type'),
			$order->getPayment()->getData('cc_owner'),
			$cc_number,
			$cc_expire,
			$checkmo_bank,
			$checkmo_acct,
			$checkmo_route,
			$checkmo_number
        );
    }

}

?>