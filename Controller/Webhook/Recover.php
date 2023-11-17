<?php
namespace Creditea\Magento2\Controller\Webhook;

use Creditea\Magento2\Helper\Log;
use Creditea\Magento2\Helper\Data;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Controller\ResultFactory;


class Recover extends \Magento\Framework\App\Action\Action
{  
    protected $cart;
    protected $helper;
    protected $helperLog;
    protected $product;
    protected $request;
    protected $quoteFactory;
    protected $messageManager;
    protected $checkoutSession;
    
    public function __construct(
        Cart $cart,
        Data $helper,
        Http $request,
        Log $helperLog,
        Context $context,
        Session $checkoutSession,
        QuoteFactory $quoteFactory,
        ProductRepository $product
    ){
        parent::__construct($context);
        $this->cart = $cart;
        $this->helper = $helper;
        $this->request = $request;
        $this->product = $product;
        $this->helperLog = $helperLog;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $context->getMessageManager();
    }
 
    public function execute()
    {
        $recover = $this->recover();
        if($recover){
            return $this->responseSuccess();
        }
        return $this->responseError();
    }


    public function recover() : bool
    {
        $response = false;
        $quoteId = $this->request->getParam('id') ?? '';

        if($quoteId != ''){

            try {
                $quote = $this->quoteFactory->create()->load($quoteId);
                if($quote->hasData()){

                    $recoverItems = [];
                    $items = $quote->getAllVisibleItems();
        
                    foreach($items as $key => $item){
                        $recoverItems[$key]['sku'] = $item->getSku();
                        $recoverItems[$key]['qty'] = $item->getQty();
                        $recoverItems[$key]['product_id'] = $item->getProductId();
                        $recoverItems[$key]['product_type'] = $item->getProductType();
                        
                        /* For product configurable */
                        if($item->getProductType() == 'configurable'){
                            $selectOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                            $recoverItems[$key]['super_attribute'] = $selectOptions['info_buyRequest']['super_attribute'] ?? [];
                        }

                        /* For product bundle */
                        if($item->getProductType() == 'bundle'){
                            $selectOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                            $recoverItems[$key]['bundle_option'] = $selectOptions['info_buyRequest']['bundle_option'] ?? [];
                            $recoverItems[$key]['bundle_option_qty'] = $selectOptions['info_buyRequest']['bundle_option_qty'] ?? [];
                        }
                    }

                    $this->cart->truncate();
        
                    foreach ($recoverItems as $item) {
                        $product = $this->product->getById($item['product_id']);
                        $params = [];
                        $params['qty'] = $item['qty'];
                        $params['product'] = $product->getId();
                        if($item['product_type'] == 'configurable'){
                            $params['super_attribute'] = $item['super_attribute'];
                        }
                        if($item['product_type'] == 'bundle'){
                            $params['bundle_option'] = $item['bundle_option'];
                            $params['bundle_option_qty'] = $item['bundle_option_qty'];
                        }
                        $this->cart->addProduct($product, $params);
                    }
                    
                    $this->cart->save();
                    
                    if($this->cart->getItemsCount() > 0){
                        $newQuote = $this->checkoutSession->getQuote();
                        $newQuote->collectTotals()->save();
                        $newQuote->save();
                        $response = true;
                    }
                }
            } catch (\Throwable $th) {
                $this->helperLog->log($th);
            }
        }
        return $response;
    }

    public function responseSuccess()
    {
        /*$this->messageManager->addSuccess(__('Cart recovered')); */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout/cart');
        return $resultRedirect;
    }

    public function responseError()
    {
        /* $this->messageManager->addError(__("Check cart's data"));*/
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout/cart');
        return $resultRedirect;
    }
}