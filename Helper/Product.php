<?php
namespace Creditea\Magento2\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;

class Product extends AbstractHelper
{
    protected $context;
    protected $objectManager;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager
    )
    {
        parent::__construct($context);
		$this->objectManager = $objectManager;
    }

    public function createObject($path, $arguments = [])
    {
        return $this->objectManager->create($path, $arguments);
    }

    public function getObject($path)
    {
        return $this->objectManager->get($path);
    }

    public function loadProductBySku($sku)
    {
        return $this->getObject('\Magento\Catalog\Model\ProductRepository')->get($sku);
    }

    public function loadProductById($id)
    {
        return $this->getObject('\Magento\Catalog\Model\ProductRepository')->getById($id);
    }
}
