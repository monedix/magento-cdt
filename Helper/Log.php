<?php
namespace Creditea\Magento2\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;


class Log extends AbstractHelper
{
	protected $context;
    protected $moduleList;

	public function __construct(
		Context $context,
		ModuleListInterface $moduleList
    ) {
		parent::__construct($context);
		$this->moduleList = $moduleList;
    }

    public function versionMagentoCompare($ver, $operator = '>=')
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get(ProductMetadataInterface::class);
        $version = $productMetadata->getVersion();
        return version_compare($version, $ver, $operator);
    }
    
    public function log($message, $file = null, $level = null)
    {  
        try {
            $name = ($file == null || $file == false ? 'creditea_magento2.log' : $file);

			if($this->versionMagentoCompare('2.4.3')){
				$writer = new \Zend_Log_Writer_Stream(BP.'/var/log/'.$name);
				$logger = new \Zend_Log();
			}else{
				$writer = new \Zend\Log\Writer\Stream(BP.'/var/log/'.$name);
				$logger = new \Zend\Log\Logger();
			}

            $logger->addWriter($writer);
            
            $m = $message;
            if(is_array($message)){$m = json_encode($message);}

            if($this->versionMagentoCompare('2.4.3')){
				$logger->info($m);
			}else{
				$l = 6;
            	$levels = ['emergency','alert','critical','error','warning','notice','info','debug'];
            	if(($level != null || $level != false) && in_array($level, $levels)){
                	$l = array_search($level, $levels);
            	} 
				$logger->log($l,$m);
			}
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
