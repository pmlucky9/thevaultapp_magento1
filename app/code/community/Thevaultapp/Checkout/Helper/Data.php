<?php
class Thevaultapp_Checkout_Helper_Data extends Mage_Core_Helper_Abstract
{
  function getPaymentGatewayUrl() 
  {
    return Mage::getUrl('thevaultapp/payment/gateway', array('_secure' => false));
  }
}