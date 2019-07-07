<?php
class Thevaultapp_Checkout_PaymentController extends Mage_Core_Controller_Front_Action 
{ 
  /**
   * Returns the order instance.
   *
   * @return \Magento\Sales\Model\Order
   * @throws DomainException
   */
  private function getAssociatedOrder($orderId) {
    $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
    if(!isset($order) || is_null($order) || $order->isEmpty()) {
        throw new Exception('The order does not exists.');
    }
    return $order;
  }

  /**
   * Prepare JSON formatted data for response to client
   *
   * @param $response
   * @return Zend_Controller_Response_Abstract
   */
  protected function _prepareDataJSON($response)
  {
      $this->getResponse()->setHeader('Content-type', 'application/json', true);
      return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
  }

  /**
   * Returns transformed amount by the given currency code which can be handled by the store.
   *
   * @param string|int $amount Value from the gateway.
   * @param $currencyCode
   * @return float
   */
  public function getStoreAmountOfCurrency($amount, $currencyCode) {
    $currencyCode   = strtoupper($currencyCode);
    $amount         = (int) $amount;
    return (float) ($amount / 100);
  }

  /**
   * Check order status.
   */
  public function checkorderstatusAction() {
    $params = $this->getRequest()->getPost();
    try {
      //$orderNumbers = "['". $params['orderId'] . "']";
      $orderNumbers = $params['orderId'];
      $orders = Mage::getModel('sales/order')->getCollection()
              ->addFieldToFilter('increment_id', array('in' => $orderNumbers))
              ->addAttributeToSelect('customer_email')
              ->addAttributeToSelect('state');  

      if ($orders->count() == 0)
      {
        return $this->_prepareDataJSON([
          "status" => 'error',
          "errors" => 'wrong order'
        ]);
      }
      
      $order = $orders->getFirstItem();
      $status = $order->getState();
      return $this->_prepareDataJSON([
        "status" => 'ok',
        "data" => $status
      ]);
    } catch (Exception $error) {
      return $this->_prepareDataJSON([
        "status" => 'error',
        "errors" => 'wrong order'
      ]);
    }
    
  }

  /**
   * Handles the controller method.
   *
   */
  public function vaultrequestorderAction() {

    // Retrieve the request parameters     
    $apiUrl = Mage::getStoreConfig('payment/thevaultapp/api_url');
    $token = Mage::getStoreConfig('payment/thevaultapp/api_key');   
    $store = Mage::getStoreConfig('payment/thevaultapp/store_name');    
    $quantity = 1;

    // Load order information
    $order = new Mage_Sales_Model_Order();
    $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();    
    $order->loadByIncrementId($orderId);
    $phone = $order->getBillingAddress()->getTelephone();

    if ($order) {
      $subid1 = $orderId;
      $amount = $order->getGrandTotal();
      $amount = number_format(floatval($amount), 2);
      $params = array (
        'token' => $token,
        'store' => $store,        
        'quantity' => $quantity,
        'subid1' => $subid1,
        'phone' => $phone,
        'amount' => $amount
      );
      $client = new Zend_Http_Client();
      $client->setUri($apiUrl);
      $client->setMethod('POST');
      $client->setHeaders('Content-Type', 'application/json');
      $client->setHeaders('Accept','application/json');        
      $client->setRawData(json_encode($params), 'application/json');        
      $response= $client->request('POST');      

      try {
        $result = json_decode($response->getBody(), true);            
        
        if ($result['status'] == 'ok') {
          return $this->_prepareDataJSON([
            'status' => 'ok',
            'data' => [
              'token' => $token,
              'store' => $store,              
              'quantity' => $quantity,
              'subid1' => $subid1,
              'requestid' => $result['data']['requestid'],
              'count' => $result['data']['count'],
              'phone' => $result['data']['phone'],
              'amount' => $amount,
            ]
          ]);
        } else {
          $result = array_merge([
            'data' => [
              'token' => $token,
              'store' => $store,              
              'quantity' => $quantity,
              'subid1' => $subid1,
              'amount' => $amount,
            ]
          ], $result);
          return $this->_prepareDataJSON($result);
        }
      } catch(Exception $err) {
        return $this->_prepareDataJSON([
          "status" => 'error',
          "errors" => 'json parse error',
          'data' => [
              'token' => $token,
              'store' => $store,              
              'quantity' => $quantity,
              'subid1' => $subid1,
              'amount' => $amount,
          ]
        ]);
      }
    }
    return $this->_prepareDataJSON([
        "status" => 'error',
        "errors" => 'wrong order'
    ]);
  }
 
  /**
   * Callback function for vaultapp.com
   */
  public function vaultcallbackAction()
  {
    $request_text = file_get_contents('php://input');
    file_put_contents('callback.txt', $request_text);
    $obj = [];
    $order = null;
    $payment = null;
    $status = '';
    $methodId = null;
    try {
      $obj = json_decode($request_text, true);
      $order = isset($obj['subid1']) ? $this->getAssociatedOrder($obj['subid1']) : null;
      if (!is_null($order)) {
        $payment = $order->getPayment();
        $status = strtolower(trim($obj['status']));
        $methodId = $payment->getMethodInstance()->getCode();
      }
    } catch (Exception $ex) {

    }

    if (is_null($order)) {
        return $this->_prepareDataJSON([
            "status" => "failed",
            "request_text" => $request_text
        ]);
    }        
    // Update order status  
    try {
      if ($status == 'approved') {
        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save();              
        $order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE)->save();
        $order->setStatus("complete"); 
      } else {        
        //$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
        $order->setData('state', Mage_Sales_Model_Order::STATE_CANCELED)->save();
        $order->setStatus("complete"); 
      }
    } catch (Exception $e) {
      return $this->_prepareDataJSON([
        "status" => "failed",
        "request_text" => $e
      ]);     
    }
    
    // Send the email only if it hasn't been sent
    if (!$order->getEmailSent()) {      
      $order->setEmailSent(true);
    }

    // Delete comments history
    foreach ($order->getAllStatusHistory() as $orderComment) {
      $orderComment->delete();
    }

    if ($status == 'approved') {
        // Create new comment
        $newComment = 'Authorized amount of ' . $this->getStoreAmountOfCurrency($obj['amount'] * 100, 'USD') . ' ' . 'USD' . ' Transaction ID: ' . $obj['tid'];

        // Add the new comment
        $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);

        // Generate the invoice
        $amount = $this->getStoreAmountOfCurrency(
            $obj['amount'] * 100,
            'USD'
        );

        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        //$invoice->setTransactionId($obj['tid']);
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);      
        $invoice->setBaseGrandTotal($amount);
        $invoice->register();

        // Save the invoice
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transactionSave->save();
    }

    $order->save();
    
    return $this->_prepareDataJSON([
      "status" => $status,
    ]);

  }
  
}