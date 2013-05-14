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

 * @author     Jonathan Feist <sales@webshopapps.com>

 * */

class Ip_Ordermanager_Model_Export_Highrise extends Mage_Core_Model_Abstract

{ 

    

	public function exportOrders($orders)

	{

	    $this->highrise_url = Mage::getStoreConfig('order_export/highrise/highrise_url');

	    $this->api_token = Mage::getStoreConfig('order_export/highrise/highrise_api_key');

	    if ((strpos($this->highrise_url, "http://")!== 0) && (strpos($this->highrise_url, "https://")!== 0) ) {

	    	Mage::throwException('Error: Incorrect URL for Highrise in configuration');

	    }

    	

		$this->highrise_notes = Mage::getStoreConfig('order_export/highrise/highrise_notes');

		$this->highrise_background = Mage::getStoreConfig('order_export/highrise/highrise_background');

    	$results = array();

        foreach ($orders as $order) {

        

            $order = Mage::getModel('sales/order')->loadByAttribute('entity_id',$order);

            $items = $order->getAllItems();

            $customerDetails = $order->getBillingAddress();

            $products = '';

            

            for($i=0;$i<count($items);$i++)

            {

                $products .= $items[$i]->getName().', ';

            }

            $request = array('sFirstName'=>$customerDetails->getFirstname(),

             'sLastName'=>$customerDetails->getLastname(),

             'sCompany'=>$customerDetails->getCompany(),

             'sPhone'=>$customerDetails->getTelephone(),

             'sStreet'=>$customerDetails->getStreet(1),

             'sCity'=>$customerDetails->getCity(),

             'sState'=>$customerDetails->getRegion(),

             'sCountry'=>$customerDetails->getCountry(),

             'sZip'=>$customerDetails->getPostcode(),

             'sEmail'=>$order->getData('customer_email'),

             'sOrderNo'=>$order->getData('increment_id'),

             'sOrderDate'=>$order->getData('created_at'),

             'sProducts'=>$products, 'status' => '');

			 

            $request = $this->_query_contact($request);

            $results[$request['sOrderNo']] = $request['status'];

        }

        return $results;

	}

	

	private function _query_contact($request){

		$id = $this->_person_in_highrise($request);

		if($id < 0){

			$curl = curl_init($this->highrise_url.'/people.xml');

			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

			curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x'); 

			

			if($this->highrise_background){

				$body = "OrderNo: ".$request['sOrderNo']."<br />

						 Date: ".$request['sOrderDate']."<br />

						 Products: ".$request['sProducts'];

			}else{

				$body = "";

			}

			curl_setopt($curl,CURLOPT_HTTPHEADER,Array("Content-Type: application/xml"));

			curl_setopt($curl,CURLOPT_POST,true);

			curl_setopt($curl,CURLOPT_POSTFIELDS,'<person>

				<first-name>'.htmlspecialchars($request['sFirstName']).'</first-name>

				<last-name>'.htmlspecialchars($request['sLastName']).'</last-name>

				<background>'.htmlspecialchars($body).'</background>

				<company-name>'.htmlspecialchars($request['sCompany']).'</company-name>

				<contact-data>

					<email-addresses>

						<email-address>

							<address>'.htmlspecialchars($request['sEmail']).'</address>

							<location>Work</location>

						</email-address>

					</email-addresses>

				<phone-numbers>

					<phone-number>

						<number>'.htmlspecialchars($request['sPhone']).'</number>

						<location>Work</location>

					</phone-number>

				</phone-numbers>

				<addresses>

					<address>

					  <city>'.htmlspecialchars($request['sCity']).'</city>

					  <country>'.htmlspecialchars($request['sCountry']).'</country>

					  <state>'.htmlspecialchars($request['sState']).'</state>

					  <street>'.htmlspecialchars($request['sStreet']).'</street>

					  <zip>'.htmlspecialchars($request['sZip']).'</zip>

					  <location>Work</location>

					</address>

				  </addresses>

				</contact-data>

			</person>');

			

			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);

			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

			if (!$curl) {

	            Mage::throwException('Error: unable to connect to ' .$this->highrise_url.'/people.xml');

			}

			$xml = curl_exec($curl);

			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			curl_close($curl);

			if ($http_code = 201) {

				

				$request['status'] =   Ip_Ordermanager_Model_Export_Status::STATUS_CREATED;

				$id = $this->_person_in_highrise($request);

				if(!$this->highrise_background && $this->highrise_notes){

					$this->_order_to_notes($request,$id);

				}

			}

			else {

				$request['status'] =   Ip_Ordermanager_Model_Export_Status::STATUS_FAILED;

			}

		}elseif($this->highrise_notes && !$this->_order_in_highrise($request,$id)){

		    $updateStatus = $this->_order_to_notes($request,$id);

		    if ($updateStatus == 201) {

		    	$request['status'] = Ip_Ordermanager_Model_Export_Status::STATUS_UPDATED;

		    }

		    else {

		    	$request['status'] =   Ip_Ordermanager_Model_Export_Status::STATUS_FAILED;

		    }

		    

		}else{ 

			$request['status'] =   Ip_Ordermanager_Model_Export_Status::STATUS_DUPLICATE;

		}

		return $request;

	}

	

	private function _order_to_notes($request,$id){

		$curl = curl_init($this->highrise_url.'/notes.xml');

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

		curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x'); 

		

		$body = "OrderNo: ".$request['sOrderNo']."<br />

				 Date: ".$request['sOrderDate']."<br />

				 Products: ".$request['sProducts'];

		

		curl_setopt($curl,CURLOPT_HTTPHEADER,Array("Content-Type: application/xml"));

		curl_setopt($curl,CURLOPT_POST,true);

		curl_setopt($curl,CURLOPT_POSTFIELDS,'<note>

			<subject-id type="integer">'.$id.'</subject-id>

			<subject-type>Party</subject-type>

			<body>'.htmlspecialchars($body).'</body>

		</note>');

		

		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);

		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		if (!$curl) {

	            Mage::throwException('Error: unable to connect to ' .$this->highrise_url.'/notes.xml');

		}

		$xml = curl_exec($curl);

		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		return $http_code;

		

	}



	private function _person_in_highrise($request){

		$curl = curl_init($this->highrise_url.'/people/search.xml?criteria[email]='.urlencode($request['sEmail']));

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

		curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x');

		

		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);

		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		if (!$curl) {

	            Mage::throwException('Error: unable to connect to ' .$this->highrise_url.'/people/search.xml?criteria[email]='.urlencode($request['sEmail']));

		}

		$xml = curl_exec($curl);

		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($http_code == 200) {

			$people = simplexml_load_string($xml);

			$id = '-1';

			for($i=0;$i<count($people->person);$i++) {

				if($people->person[$i] != null) {

					$id = $people->person[$i]->id;

				}	

			}

		}

		

		

		return $id;

	}

	

    private function _order_in_highrise($request,$id){

		$c = true;

		$x = 0;

		while($c){

			$curl = curl_init($this->highrise_url.'/people/'.$id.'/notes?n='.$x);

			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

			curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x');

			

			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);

			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

			if (!$curl) {

	            Mage::throwException('Error: unable to connect to ' .$this->highrise_url.'/people/'.$id.'/notes?n='.$x);

			}

			$xml = curl_exec($curl);

			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			curl_close($curl);

			if ($http_code == 200) {

				$notes = simplexml_load_string($xml);

				for($i=0;$i<count($notes->note);$i++) {

					if($notes->note[$i] != null) {

						if(strpos($notes->note[$i]->body,$request['sOrderNo'])!=false){

							return true;

						}

					}

				}

				if((count($notes)%25==0)&&(count($notes)>0)){

					$x += 25;

				}else{

					$c = false;

				}

			}

		}

		return false;

	}

	

	private function _order_in_background_info($request,$id){

	    $curl = curl_init($this->highrise_url.'/people/'.$id.'.xml');

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

		curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x');



	    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);

		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		

		if (!$curl) {

	            Mage::throwException('Error: unable to connect to ' .$this->highrise_url.'/people/'.$id.'.xml');

		}

		$xml = curl_exec($curl);

		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		

		if ($http_code == 200) {

	    	$people = simplexml_load_string($xml);

	    	for($i=0;$i<count($people->person);$i++) {

				if($people->person[$i] != null) {

					if(strpos($people->person[$i]->background,$request['sOrderNo'])!=false){

						return true;

					}

				}	

			}

		}

		return false;

	}



}