<?php

class Ip_Ordermanager_Model_Options_Options

{

 

	public function toOptionArray()

    {

        return array(

            array('value' => 'Standard', 'label'=>Mage::helper('adminhtml')->__('Standard')),

            array('value' => 'Sage', 'label'=>Mage::helper('adminhtml')->__('Sage')),

            array('value' => 'Highrise', 'label'=>Mage::helper('adminhtml')->__('Highrise'))

        );

    }







}