<?php

class Thevaultapp_Checkout_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {
  protected $_code  = 'thevaultapp'; 
  protected $_infoBlockType = 'thevaultapp/info_info';
  
  public function validate()
  {
    parent::validate();
    $info = $this->getInfoInstance();
    
    // get phone address
    $billing = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();  
    
    $phoneNumber = $billing->getData('telephone');
    
    if ($phoneNumber == '' || $phoneNumber == NULL) {
      $errorMsg = 'Phone number should be inputed!';
      Mage::throwException($errorMsg);
    }

    return $this;
  }
 }