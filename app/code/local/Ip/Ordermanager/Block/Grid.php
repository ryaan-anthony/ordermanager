<?php

class Ip_Ordermanager_Block_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid 
{

    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();   	
		
        $this->getMassactionBlock()->addItem('ordermanager', array(
             'label'=> 'Export Order Details',
             'url'  => $this->getUrl('*/export/csvexport')
        ));

    }

}