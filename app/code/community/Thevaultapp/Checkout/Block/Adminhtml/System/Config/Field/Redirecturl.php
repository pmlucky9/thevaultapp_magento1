<?php

class Thevaultapp_Checkout_Block_Adminhtml_System_Config_Field_Redirecturl
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{ 
    /**
     * Render Information element
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
       /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    { 
        $callbackUrl = $this->getBaseUrl() . 'thevaultapp/payment/vaultCallback';

        $element->setData('value', $callbackUrl);
        $element->setReadonly('readonly');

        return $element->getElementHtml();
    }
}
