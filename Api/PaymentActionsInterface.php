<?php
namespace Creditea\Magento2\Api;

interface PaymentActionsInterface
{
    /**
     * Update Order
     * @param string $orderId
     * @return string
     */
    public function paymentCallBack($orderId = 0);

    /**
     * Cancel Order
     * @param string $orderId
     * @return string
     */
    public function paymentReturn($orderId = 0);
}