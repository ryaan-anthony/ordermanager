<?php

class Ip_Ordermanager_Adminhtml_ExportController extends Mage_Adminhtml_Controller_Action
{

    public function csvexportAction()
    {
    	$orders = $this->getRequest()->getPost('order_ids', array());		
		$file = Mage::getModel('ordermanager/export_csv')->exportOrders($orders);  
		$this->_prepareDownloadResponse($file, file_get_contents(Mage::getBaseDir('export').'/'.$file));    
    }

}