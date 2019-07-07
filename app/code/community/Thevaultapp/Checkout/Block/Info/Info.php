<?php

class Thevaultapp_Checkout_Block_Info_Info extends Mage_Payment_Block_Info
{
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('thevaultapp/block/info.phtml');
  }
  
  protected function _prepareSpecificInformation($transport = null)
  {
    if (null !== $this->_paymentSpecificInformation) 
    {
      return $this->_paymentSpecificInformation;
    }
     
    $data = array(); 
 
    $transport = parent::_prepareSpecificInformation($transport);
     
    return $transport->setData(array_merge($data, $transport->getData()));
  }
}