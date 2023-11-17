<?php
namespace Creditea\Magento2\Controller\Webhook;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Redirect extends \Magento\Framework\App\Action\Action
{
    private $checkoutSession;

    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $order = $this->checkoutSession->getLastRealOrder();

        if($order){
            $info = $order->getPayment()->getAdditionalInformation();
            $resultRedirect->setUrl($info['url'].'&token='.$info['token'] ?? '/');
            return $resultRedirect;
        }

        $resultRedirect->setUrl('/');

        return $resultRedirect;
    }
}
