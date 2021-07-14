<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;

use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\Data;
use Straker\EasyTranslationPlatform\Logger\Logger;

class CheckLanguagePairs extends Action
{
    protected $_storeManager;
    protected $_resultJson;
    protected $_configHelper;
    protected $_logger;
    protected $_dataHelper;

    public $resultRedirectFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJson,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper,
        Logger $logger,
        Data $data
    ) {
        $this->_storeManager = $storeManager;
        $this->_resultJson = $resultJson;
        $this->_configHelper = $configHelper;
        $this->_logger = $logger;
        $this->_dataHelper = $data;

        return parent::__construct($context);
    }

    public function execute()
    {
        $target_store_id = $this->getRequest()->getPost('target_store_id');
        $store_data = $this->_configHelper->getStoreInfo($target_store_id);
        $result = $this->_resultJson->create();
        $result = $result->setData(['store-data' => $store_data]);
        return $result;
    }
}
