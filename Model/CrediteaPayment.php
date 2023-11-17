<?php
namespace Creditea\Magento2\Model;
use Creditea\Magento2\Helper\Data;

class CrediteaPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'creditea_magento2';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canOrder = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_isOffline = true;
    protected $scope_config;
    protected $is_sandbox;
    protected $supported_currency_codes = array('MXN');
    protected $logger;
    protected $_storeManager;
    protected $_inlineTranslation;
    protected $_directoryList;
    protected $_file;

    protected $webApi;
    protected $helper;
    protected $helperLog;

    public function __construct(
        \Magento\Framework\Model\Context $context, 
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, 
        \Magento\Payment\Helper\Data $paymentData, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger_interface,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Creditea\Magento2\Helper\Data $helper,
        \Creditea\Magento2\Helper\Log $helperLog,
        \Creditea\Magento2\Service\WebApi $webApi,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry, 
            $extensionFactory,
            $customAttributeFactory,
            $paymentData, 
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
        
        $this->customerModel = $customerModel;
        $this->customerSession = $customerSession;
        $this->assetRepository = $assetRepository;

        $this->_file = $file;
        $this->_directoryList = $directoryList;
        $this->logger = $logger_interface;
        $this->_inlineTranslation = $inlineTranslation;        
        $this->_storeManager = $storeManager;
        $this->scope_config = $scopeConfig;
        
        $this->webApi = $webApi;
        $this->helper = $helper;
        $this->helperLog = $helperLog;
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {            
        $line_items = [];
        $urlApi = Data::URL_API;
        $order = $payment->getOrder();
        $key = $this->helper->getApiKey();
        $billing = $order->getBillingAddress();
        $store_base_url = $this->helper->getStoreData()->getBaseUrl();
        
        if ($key == '') {
            $this->helperLog->log(__('API key is required'));
            throw new \Magento\Framework\Validator\Exception(__('API key is required'));
        }

        try {

            $store_tz = $this->helper->getConfigValue('general/locale/timezone');
            date_default_timezone_set($store_tz);

			foreach ($order->getAllVisibleItems() as $item) {
				$line_items[] = [
					'sku' => $item->getSku(),
					'description' => $item->getTitle() ?? $item->getSku(),
					'price' => $item->getPrice(),
					'quantity' => $item->getQtyOrdered(),
					'total' => strval($item->getPrice() * $item->getQtyOrdered()),
					'gtin' => 'ITEM BARCODE'
				];
			}

			$jsonOrder = array(
				'orderID' => strval($order->getIncrementId()),
				'value' => (is_numeric($order->getGrandTotal())) ? $order->getGrandTotal() : 0,
				'currency' => 'MXN',
				'country' => 'México',
                'line_items' => $line_items,
                'city' => $billing->getCity() ?? '',
				'state' => $billing->getRegion() ?? '',
				'postcode' => $billing->getPostcode() ?? '00000',
				'returnUrl' => $store_base_url . 'rest/V1/creditea/payment-return',
				'callbackUrl' => $store_base_url . 'rest/V1/creditea/payment-call-back'
			);

            $this->helperLog->log('jsonOrder: ' . json_encode($jsonOrder));

            $request = $this->webApi->doRequest($urlApi, $jsonOrder, 'POST'); 
            
            if ($request->getStatusCode() == 200) {
                
                $response = $request->getBody()->__toString();
                $response = stripslashes($response);
                $responseData = json_decode($response, true);         

                if(isset($responseData['url']) && isset($responseData['token'])){
                    $state = \Magento\Sales\Model\Order::STATE_NEW;
                    $order->setState($state)->setStatus($state);      
                    $payment->setAdditionalInformation('url', $responseData['url'] ?? '');
                    $payment->setAdditionalInformation('token', $responseData['token'] ?? '');
                    $order->save();
                }else{
                    $this->helperLog->log(__('Error in the purchase process'));
                    $this->helperLog->log(__('dont get url or token data'));
                    throw new \Magento\Framework\Validator\Exception(__('Error in the purchase process'));
                }

            }else{
                $this->helperLog->log(__('Error in the purchase process'));
                $this->helperLog->log($request->getStatusCode());
                $this->helperLog->log($request->getReasonPhrase());
                throw new \Magento\Framework\Validator\Exception(__('Error in the purchase process'));
            }
            
        } catch (\Exception $e) {
            $this->logger->error(__($e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__($e->getMessage()));
        }

        $payment->setSkipOrderProcessing(true);

        return $this;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (empty($this->helper->getApiKey())) {
            $this->logger->error(__('API key is required'));
            $this->helperLog->log(__('API key is required'));
            return false;
        }
        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode)
    {
        return in_array($currencyCode, $this->supported_currency_codes);
    }
}
