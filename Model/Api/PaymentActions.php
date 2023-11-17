<?php
namespace Creditea\Magento2\Model\Api;

use Magento\Sales\Model\Order;
use Creditea\Magento2\Helper\Log;
use Creditea\Magento2\Helper\Data;
use Magento\Framework\Webapi\Rest\Response;

class PaymentActions
{
    protected $order;
    protected $helper;
    protected $helperLog;
    protected $response;

    public function __construct(
        Order $order,
        Data $helper,
        Log $helperLog,
        Response $response
    ){
        $this->order = $order;
        $this->helper = $helper;
        $this->response = $response;
        $this->helperLog = $helperLog;
    }
 
    public function paymentCallBack($orderId)
    {
        $response = [];
        $response['status'] = false;
        try {
            /* Validations */
            $validate = $this->validateCrediteaOrder($orderId);
            if($validate['valid'] != true){
                $response['status'] = $validate['status'];
                return $this->responseJson($response);
            }

            /* Action */
            $processState = Order::STATE_PROCESSING;
            $order = $this->order->loadByIncrementId($orderId);
            $order->setState($processState)->setStatus($processState);
            $order->getPayment()->setAdditionalInformation('process', 'webapi');
            $order->addStatusHistoryComment(__('Payment received, processing order.'));
            $order->save();
        
            $response['status'] = true;
            $response['message'] = __('Payment received, processing order.');
            $response['thank_you_url'] = $this->getBaseUrl($order).'checkout/onepage/success';

        } catch (\Exception $e) {
            $this->helperLog->log($e->getMessage());
            $response = ['status' => false, 'message' => $e->getMessage()];
        }

        return $this->responseJson($response);
    }

    public function paymentReturn($orderId)
    {        
        $response = [];
        $response['status'] = false;
        
        try {
            /* Validations */
            $validate = $this->validateCrediteaOrder($orderId);
            if($validate['valid'] != true){
                $response['status'] = $validate['status'];
                return $this->responseJson($response);
            }

            /* Action */
            $cancelState = Order::STATE_CANCELED;
            $order = $this->order->loadByIncrementId($orderId);
            $quoteId = $order->getQuoteId();
            $order->setState($cancelState)->setStatus($cancelState);
            $order->getPayment()->setAdditionalInformation('process', 'canceled');
            $order->addStatusHistoryComment(__('Payment Canceled.'));
            $order->save();
        
            $response['status'] = true;
            $response['message'] = __('Order status updated to failed');
            $response['restore_cart_url'] = $this->getBaseUrl($order).'creditea/webhook/recover/id/'.$quoteId;

        } catch (\Exception $e) {
            $this->helperLog->log($e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->responseJson($response);
    }

    public function validateCrediteaOrder($orderId) : array
    {
        $response = [];
        $response['status'] = '';
        $response['valid'] = false;
       

        /* validate field order_id */
        if($orderId == 0){ 
            $response['status'] = 'field order_id is required';
            return $response;
        }

        /* validate object order */
        $order = $this->order->loadByIncrementId($orderId);
        if(!$order->hasData()){ 
            $response['status'] = 'order not found';
            return $response;
        }

        /* validate order belongsto creditea */
        if($order->getPayment()->getMethod() != 'creditea_magento2'){ 
            $response['status'] = 'order not belongsto Creditea';
            return $response;
        }
        
        /* validate order's state */
        if($order->getState() != Order::STATE_NEW){ 
            $response['status'] = 'order not-hold';
            return $response;
        }

        $response['valid'] = true;
        $response['status'] = 'success all validations';

        return $response; 
    }

    public function getBaseUrl(Order $order) : string
    {
        return $this->helper->getStoreData($order->getStoreId())->getBaseUrl();
    }

    public function responseJson($response)
    {
        return $this->response->setHeader('Content-Type', 'application/json', true)->setBody(json_encode($response))->sendResponse();
    }
}