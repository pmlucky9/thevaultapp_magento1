<?php
class Thevaultapp_Checkout_Model_Adminhtml_Config_Environment
{
    public function toOptionArray()
    {        
        $options = array(
            array(
                'value' => 'live',
                'label' => 'live'
            ),
            array(
                'value' => 'sandbox',
                'label' => 'sandbox'
            )
         );
        return $options;
    }
}
